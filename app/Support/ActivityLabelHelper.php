<?php

namespace App\Support;

use Illuminate\Support\Str;

class ActivityLabelHelper
{
    public static function label(?string $description, ?string $subjectType): string
    {
        $description = strtolower(trim((string) $description));
        $subjectBase = $subjectType ? class_basename($subjectType) : null;

        $map = [
            'Product' => [
                'created' => 'Created product',
                'updated' => 'Updated product',
                'deleted' => 'Deleted product',
                'restored' => 'Restored product',
            ],
            'Catalog' => [
                'created' => 'Created catalog',
                'updated' => 'Updated catalog',
                'deleted' => 'Deleted catalog',
                'restored' => 'Restored catalog',
            ],
            'Category' => [
                'created' => 'Created category',
                'updated' => 'Updated category',
                'deleted' => 'Deleted category',
                'restored' => 'Restored category',
            ],
            'InventoryMovement' => [
                'created' => 'Adjusted inventory',
                'updated' => 'Adjusted inventory',
                'deleted' => 'Deleted inventory movement',
            ],
            'User' => [
                'created' => 'Created user',
                'updated' => 'Updated user',
                'deleted' => 'Deleted user',
                'restored' => 'Restored user',
            ],
            'Order' => [
                'created' => 'Created order',
                'updated' => 'Updated order',
                'deleted' => 'Deleted order',
                'restored' => 'Restored order',
            ],
        ];

        if ($subjectBase && isset($map[$subjectBase][$description])) {
            return $map[$subjectBase][$description];
        }

        $fallbackSubject = $subjectBase ? Str::of($subjectBase)->snake(' ')->headline()->lower() : 'item';
        $fallbackAction = $description !== '' ? Str::of($description)->replace('_', ' ')->headline()->lower() : 'changed';

        return Str::of($fallbackAction . ' ' . $fallbackSubject)->headline();
    }
}
