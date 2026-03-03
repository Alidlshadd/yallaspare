<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function getHumanActionAttribute(): string
    {
        return match ($this->action) {
            'inventory.adjusted' => 'Adjusted inventory',
            'order.status_changed' => 'Changed order status',
            'catalog.created' => 'Created catalog',
            'catalog.updated' => 'Updated catalog',
            'catalog.deleted' => 'Deleted catalog',
            'product.created' => 'Created product',
            'product.updated' => 'Updated product',
            'product.deleted' => 'Deleted product',
            'user.created' => 'Created user',
            'user.updated' => 'Updated user',
            'user.deleted' => 'Deleted user',
            'order.created' => 'Created order',
            'order.updated' => 'Updated order',
            'order.deleted' => 'Deleted order',
            default => Str::of((string) $this->action)
                ->replace(['.', '_'], ' ')
                ->headline()
                ->toString(),
        };
    }

    public function getDetailsSummaryAttribute(): string
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        if ($this->action === 'inventory.adjusted') {
            $type = $meta['type'] ?? null;
            $qty = $meta['quantity'] ?? null;

            $parts = array_filter([
                $type ? 'Type: ' . $type : null,
                $qty !== null ? 'Qty: ' . $qty : null,
            ]);

            return $parts ? implode(' · ', $parts) : '—';
        }

        if ($this->action === 'order.status_changed') {
            $from = $meta['from'] ?? null;
            $to = $meta['to'] ?? null;

            $parts = array_filter([
                $from ? 'From: ' . $from : null,
                $to ? 'To: ' . $to : null,
            ]);

            return $parts ? implode(' · ', $parts) : '—';
        }

        if (empty($meta)) {
            return '—';
        }

        $interesting = array_intersect_key($meta, array_flip(['quantity', 'type', 'from', 'to']));
        $data = $interesting ?: $meta;

        $parts = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $parts[] = Str::of((string) $key)->replace('_', ' ')->title() . ': ' . ($value ?? '—');
            }
        }

        return $parts ? implode(' · ', $parts) : '—';
    }

    public function getChangeLinesAttribute(): array
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $old = is_array($meta['old'] ?? null) ? $meta['old'] : [];
        $new = is_array($meta['new'] ?? null) ? $meta['new'] : [];
        $changed = is_array($meta['changed'] ?? null) ? $meta['changed'] : array_keys($new ?: $old);

        $lines = [];
        foreach ($changed as $key) {
            if (!array_key_exists($key, $old) && !array_key_exists($key, $new)) {
                continue;
            }
            $from = array_key_exists($key, $old) ? $old[$key] : null;
            $to = array_key_exists($key, $new) ? $new[$key] : null;
            $lines[] = Str::of((string) $key)->replace('_', ' ')->title()
                . ': ' . (is_scalar($from) || $from === null ? ($from ?? '—') : '[...]')
                . ' → ' . (is_scalar($to) || $to === null ? ($to ?? '—') : '[...]');
        }

        return $lines;
    }

    public function getMetaPrettyAttribute(): string
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $encoded = json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded !== false ? $encoded : '{}';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
