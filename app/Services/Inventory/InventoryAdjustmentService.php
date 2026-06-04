<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentService
{
    public function setStock(Product $product, int $stockQuantity, ?User $actor, string $reference, ?string $note = null): Product
    {
        return DB::transaction(function () use ($product, $stockQuantity, $actor, $reference, $note): Product {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $before = (int) $lockedProduct->stock_quantity;

            $lockedProduct->update(['stock_quantity' => $stockQuantity]);

            if ($before !== $stockQuantity) {
                InventoryMovement::query()->create([
                    'product_id' => $lockedProduct->id,
                    'user_id' => $actor?->id,
                    'type' => $stockQuantity > $before ? InventoryMovement::TYPE_IN : InventoryMovement::TYPE_OUT,
                    'quantity' => abs($stockQuantity - $before),
                    'stock_before' => $before,
                    'stock_after' => $stockQuantity,
                    'reference' => $reference,
                    'note' => $note,
                ]);

                AdminLogger::log('inventory.adjusted', $lockedProduct, [
                    'type' => 'set',
                    'from' => $before,
                    'to' => $stockQuantity,
                    'reference' => $reference,
                ]);
            }

            return $lockedProduct->fresh();
        });
    }

    public function move(Product $product, string $type, int $quantity, ?User $actor, string $reference, ?string $note = null): Product
    {
        return DB::transaction(function () use ($product, $type, $quantity, $actor, $reference, $note): Product {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $before = (int) $lockedProduct->stock_quantity;
            $after = $type === InventoryMovement::TYPE_IN ? $before + $quantity : $before - $quantity;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => __('Stock out movement exceeds available stock.'),
                ]);
            }

            $lockedProduct->update(['stock_quantity' => $after]);

            InventoryMovement::query()->create([
                'product_id' => $lockedProduct->id,
                'user_id' => $actor?->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference' => $reference,
                'note' => $note,
            ]);

            AdminLogger::log('inventory.adjusted', $lockedProduct, [
                'type' => $type,
                'quantity' => $quantity,
                'from' => $before,
                'to' => $after,
                'reference' => $reference,
            ]);

            return $lockedProduct->fresh();
        });
    }
}
