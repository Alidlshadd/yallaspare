<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPresenter
{
    /**
     * @return array{type:string,name:string,secondary:string,initial:string,url:?string,id:int|string|null}
     */
    public static function target(Activity $activity): array
    {
        $subject = $activity->subject;
        $properties = self::properties($activity);
        $attributes = (array) ($properties['attributes'] ?? []);
        $old = (array) ($properties['old'] ?? []);
        $snapshot = $attributes + $old;
        $type = class_basename((string) $activity->subject_type) ?: __('Record');
        $id = $activity->subject_id;

        [$name, $secondary] = match (true) {
            $subject instanceof User => [
                (string) $subject->name,
                (string) ($subject->email ?: $subject->phone),
            ],
            $subject instanceof Product => [
                (string) $subject->name,
                trim(implode(' · ', array_filter([(string) $subject->sku, (string) $subject->brand]))),
            ],
            $subject instanceof Category => [(string) $subject->name, (string) $subject->slug],
            $subject instanceof InventoryMovement => [
                __('Inventory movement #:id', ['id' => $subject->id]),
                trim(implode(' · ', array_filter([(string) $subject->reference, (string) $subject->type]))),
            ],
            $subject instanceof Order => [
                (string) ($subject->order_number ?: __('Order #:id', ['id' => $subject->id])),
                (string) $subject->status,
            ],
            $subject instanceof Coupon => [
                (string) ($subject->code ?: $subject->name),
                __('Coupon'),
            ],
            $subject instanceof Discount => [(string) $subject->name, __('Discount rule')],
            $subject instanceof Setting => [(string) $subject->key, __('System setting')],
            default => self::snapshotIdentity($snapshot, $type, $id),
        };

        $name = trim($name) !== '' ? trim($name) : __(':type record', ['type' => $type]);

        return [
            'type' => Str::of($type)->snake(' ')->headline()->toString(),
            'name' => $name,
            'secondary' => trim($secondary),
            'initial' => Str::upper(Str::substr($name, 0, 1)),
            'url' => self::targetUrl($subject),
            'id' => $id,
        ];
    }

    public static function actorRole(Activity $activity): string
    {
        $role = $activity->causer instanceof User ? (string) $activity->causer->role : '';

        return $role !== '' ? Str::of($role)->replace('_', ' ')->headline()->toString() : __('System');
    }

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            'role' => __('Role'),
            'email' => __('Email'),
            'stock_quantity' => __('Stock quantity'),
            'is_active' => __('Active status'),
            'category_id' => __('Category ID'),
            'product_id' => __('Product ID'),
            'user_id' => __('User ID'),
            'order_id' => __('Order ID'),
            default => Str::of($field)->replace('_', ' ')->headline()->toString(),
        };
    }

    public static function value(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('Not set');
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if (is_array($value)) {
            $scalars = array_filter($value, fn ($item) => is_scalar($item) || $item === null);

            return count($scalars) === count($value)
                ? implode(', ', array_map(fn ($item) => (string) $item, $scalars))
                : '[…]';
        }

        if (! is_scalar($value)) {
            return '[…]';
        }

        $formatted = (string) $value;

        if (in_array($field, ['role', 'status', 'type', 'payment_status'], true)) {
            $formatted = Str::of($formatted)->replace('_', ' ')->headline()->toString();
        }

        return Str::limit($formatted, 180);
    }

    /**
     * @return array<string, mixed>
     */
    public static function properties(Activity $activity): array
    {
        $properties = $activity->properties;

        if ($properties instanceof \Illuminate\Support\Collection) {
            return $properties->toArray();
        }

        if (is_object($properties) && method_exists($properties, 'toArray')) {
            return $properties->toArray();
        }

        if (is_string($properties)) {
            $decoded = json_decode($properties, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($properties) ? $properties : [];
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function snapshotIdentity(array $snapshot, string $type, int|string|null $id): array
    {
        $name = (string) ($snapshot['name']
            ?? $snapshot['name_en']
            ?? $snapshot['order_number']
            ?? $snapshot['code']
            ?? $snapshot['key']
            ?? $snapshot['sku']
            ?? '');
        $secondary = (string) ($snapshot['email'] ?? $snapshot['phone'] ?? $snapshot['sku'] ?? '');

        if ($name === '') {
            $name = $id ? __(':type #:id', ['type' => $type, 'id' => $id]) : __('Deleted record');
        }

        return [$name, $secondary];
    }

    private static function targetUrl(?Model $subject): ?string
    {
        return match (true) {
            $subject instanceof User && auth()->user()?->can('manage-users') => route('admin.users.show', $subject),
            $subject instanceof Product => route('admin.products.edit', $subject),
            $subject instanceof Category => route('admin.categories.edit', $subject),
            $subject instanceof Order => route('admin.orders.show', $subject),
            $subject instanceof InventoryMovement => route('admin.inventory.index', ['product_id' => $subject->product_id]),
            default => null,
        };
    }
}
