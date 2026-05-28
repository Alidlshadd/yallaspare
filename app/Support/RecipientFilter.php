<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RecipientFilter
{
    /**
     * @param array{
     *   roles?: array<int,string>,
     *   dealer_statuses?: array<int,string>,
     *   order_state?: string,
     *   locales?: array<int,string>,
     *   email_verified?: string,
     *   manual_include?: array<int,int>,
     *   manual_exclude?: array<int,int>,
     * } $filters
     */
    public function __construct(private array $filters)
    {
    }

    public function query(): Builder
    {
        $query = User::query();

        $roles = $this->arrayFilter('roles');
        if ($roles !== []) {
            $query->whereIn('role', $roles);
        }

        $dealerStatuses = $this->arrayFilter('dealer_statuses');
        if ($dealerStatuses !== [] && in_array('dealer', $roles, true)) {
            $query->whereIn('dealer_status', $dealerStatuses);
        }

        $orderState = (string) ($this->filters['order_state'] ?? 'any');
        if ($orderState === 'none') {
            $query->whereDoesntHave('orders');
        } elseif ($orderState === 'active') {
            $query->whereHas('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(90)));
        } elseif ($orderState === 'old') {
            $query->whereHas('orders', fn ($q) => $q->where('created_at', '<', now()->subDays(90)));
        }

        $locales = $this->arrayFilter('locales');
        if ($locales !== []) {
            $query->whereIn('locale_preference', $locales);
        }

        $verified = (string) ($this->filters['email_verified'] ?? 'any');
        if ($verified === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($verified === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        $excludeIds = array_map('intval', $this->arrayFilter('manual_exclude'));
        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        $includeIds = array_map('intval', $this->arrayFilter('manual_include'));
        if ($includeIds !== []) {
            // Additive: filtered set OR manually included ids.
            $query = User::query()->where(function ($outer) use ($query, $includeIds) {
                $outer->whereIn('id', $query->select('id'))
                      ->orWhereIn('id', $includeIds);
            });
        }

        return $query;
    }

    public function normalize(): array
    {
        return [
            'roles' => $this->arrayFilter('roles'),
            'dealer_statuses' => $this->arrayFilter('dealer_statuses'),
            'order_state' => (string) ($this->filters['order_state'] ?? 'any'),
            'locales' => $this->arrayFilter('locales'),
            'email_verified' => (string) ($this->filters['email_verified'] ?? 'any'),
            'manual_include' => array_values(array_map('intval', $this->arrayFilter('manual_include'))),
            'manual_exclude' => array_values(array_map('intval', $this->arrayFilter('manual_exclude'))),
        ];
    }

    private function arrayFilter(string $key): array
    {
        $value = $this->filters[$key] ?? [];

        return is_array($value) ? array_values($value) : [];
    }
}
