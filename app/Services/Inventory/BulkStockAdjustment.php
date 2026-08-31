<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Works out what a bulk stock change would do, and then does it.
 *
 * Two steps on purpose. The administrator sees every line resolved against the
 * catalogue — what was found, what it holds now, what it would hold — before
 * anything is written, and a run with a single unresolved line cannot be
 * applied at all. Half an applied stock correction is worse than none: you
 * cannot tell by looking which half went through.
 */
class BulkStockAdjustment
{
    public const STATUS_OK = 'ok';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_AMBIGUOUS = 'ambiguous';

    public const STATUS_INSUFFICIENT = 'insufficient';

    /**
     * Resolve parsed rows against the catalogue without changing anything.
     *
     * @param  array<int, array{sku: string, change: int, reference: ?string, note: ?string, line: int}>  $rows
     * @return array{rows: array<int, array<string, mixed>>, applicable: bool, totals: array{rows: int, blocked: int, added: int, removed: int}}
     */
    public function preview(array $rows): array
    {
        $merged = $this->mergeBySku($rows);
        $products = $this->resolve(array_values(array_unique(array_map(
            fn (array $row): string => $this->key($row['sku']),
            $merged,
        ))));

        $resolved = [];
        $blocked = 0;
        $added = 0;
        $removed = 0;

        foreach ($merged as $row) {
            $key = $this->key($row['sku']);
            $match = $products[$key] ?? null;
            $entry = [
                'sku' => $row['sku'],
                'change' => $row['change'],
                'lines' => $row['lines'],
                'reference' => $row['reference'],
                'note' => $row['note'],
                'product_id' => null,
                'product_name' => null,
                'stock_before' => null,
                'stock_after' => null,
                'status' => self::STATUS_OK,
                'message' => null,
            ];

            if ($match === null) {
                $entry['status'] = self::STATUS_NOT_FOUND;
                $entry['message'] = __('No product carries this code.');
                $resolved[] = $entry;
                $blocked++;

                continue;
            }

            if ($match === 'ambiguous') {
                $entry['status'] = self::STATUS_AMBIGUOUS;
                $entry['message'] = __('More than one product carries this code. Use the exact SKU.');
                $resolved[] = $entry;
                $blocked++;

                continue;
            }

            $before = (int) $match->stock_quantity;
            $after = $before + $row['change'];

            $entry['product_id'] = (int) $match->id;
            $entry['product_name'] = $match->localizedName();
            $entry['stock_before'] = $before;
            $entry['stock_after'] = $after;

            if ($after < 0) {
                $entry['status'] = self::STATUS_INSUFFICIENT;
                $entry['message'] = __('Only :available in stock.', ['available' => $before]);
                $blocked++;
            } elseif ($row['change'] > 0) {
                $added += $row['change'];
            } else {
                $removed += abs($row['change']);
            }

            $resolved[] = $entry;
        }

        return [
            'rows' => $resolved,
            'applicable' => $blocked === 0 && $resolved !== [],
            'totals' => [
                'rows' => count($resolved),
                'blocked' => $blocked,
                'added' => $added,
                'removed' => $removed,
            ],
        ];
    }

    /**
     * Apply a previewed run, all of it or none of it.
     *
     * The stock each row was previewed against is checked again under a lock.
     * Between looking and confirming, a customer may have bought the last of
     * something; applying the change the administrator saw would then write a
     * number nobody intended.
     *
     * @param  array<int, array<string, mixed>>  $previewRows
     * @return array{reference: string, applied: int}
     *
     * @throws RuntimeException when the catalogue moved under the preview
     */
    public function apply(array $previewRows, string $reason, ?User $actor): array
    {
        $rows = array_values(array_filter(
            $previewRows,
            static fn (array $row): bool => ($row['status'] ?? null) === self::STATUS_OK && ! empty($row['product_id'])
        ));

        if ($rows === [] || count($rows) !== count($previewRows)) {
            throw new RuntimeException(__('This run still has rows that cannot be applied.'));
        }

        $reference = 'BULK-'.Carbon::now()->format('Ymd').'-'.strtoupper(Str::random(4));

        DB::transaction(function () use ($rows, $reason, $actor, $reference): void {
            // Locked in a fixed order. Two runs touching the same products in
            // different orders would otherwise be able to deadlock each other.
            $ids = array_map(static fn (array $row): int => (int) $row['product_id'], $rows);
            sort($ids);

            $locked = Product::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $moved = [];

            foreach ($rows as $row) {
                $product = $locked->get((int) $row['product_id']);

                if (! $product) {
                    throw new RuntimeException(__('":sku" is no longer in the catalogue. Nothing was applied.', ['sku' => $row['sku']]));
                }

                $before = (int) $product->stock_quantity;

                if ($before !== (int) $row['stock_before']) {
                    throw new RuntimeException(__('Stock for ":sku" changed while you were reviewing — it now holds :now, not :then. Nothing was applied; check the list again.', [
                        'sku' => $row['sku'],
                        'now' => $before,
                        'then' => (int) $row['stock_before'],
                    ]));
                }

                $change = (int) $row['change'];
                $after = $before + $change;

                if ($after < 0) {
                    throw new RuntimeException(__('":sku" would fall below zero. Nothing was applied.', ['sku' => $row['sku']]));
                }

                $product->update(['stock_quantity' => $after]);

                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $actor?->id,
                    'type' => $change > 0 ? InventoryMovement::TYPE_IN : InventoryMovement::TYPE_OUT,
                    'quantity' => abs($change),
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference' => $reference,
                    'note' => $this->noteFor($reason, $row),
                    'performed_at' => Carbon::now(),
                ]);

                $moved[] = ['sku' => $row['sku'], 'from' => $before, 'to' => $after];
            }

            AdminLogger::log('inventory.bulk_adjusted', null, [
                'reference' => $reference,
                'reason' => $reason,
                'rows' => count($moved),
                'source' => 'bulk_stock_adjustment',
            ]);
        });

        return ['reference' => $reference, 'applied' => count($rows)];
    }

    /**
     * The reason the run was made, plus whatever the row itself carried.
     *
     * @param  array<string, mixed>  $row
     */
    private function noteFor(string $reason, array $row): string
    {
        $rowNote = trim((string) ($row['note'] ?? ''));

        return $rowNote === '' ? $reason : $reason.' — '.$rowNote;
    }

    /**
     * Fold repeated codes into one line, adding their changes up.
     *
     * A file listing the same part twice is a normal thing — two deliveries,
     * one sheet. Applying both rows separately would be right; showing only
     * one of them would not. So they are added together and the preview says
     * which lines they came from.
     *
     * @param  array<int, array{sku: string, change: int, reference: ?string, note: ?string, line: int}>  $rows
     * @return array<string, array{sku: string, change: int, lines: array<int, int>, reference: ?string, note: ?string}>
     */
    private function mergeBySku(array $rows): array
    {
        $merged = [];

        foreach ($rows as $row) {
            $key = $this->key($row['sku']);
            // PHP converts a decimal-looking string used as an array key to
            // an integer. Prefixing the internal bucket keeps a code such as
            // 6731803009 a string all the way through strict matching.
            $bucket = 'code:'.$key;

            if (! isset($merged[$bucket])) {
                $merged[$bucket] = [
                    'sku' => $row['sku'],
                    'change' => 0,
                    'lines' => [],
                    'reference' => $row['reference'],
                    'note' => $row['note'],
                ];
            }

            $merged[$bucket]['change'] += (int) $row['change'];
            $merged[$bucket]['lines'][] = (int) $row['line'];
            $merged[$bucket]['reference'] ??= $row['reference'];
            $merged[$bucket]['note'] ??= $row['note'];
        }

        return $merged;
    }

    /**
     * Find each code's product, or say that the code does not identify one.
     *
     * `sku` is unique, so an exact hit there is the answer. Part and OEM
     * numbers are not unique, and the old importer took whichever row came
     * back first — quietly adjusting a product nobody chose. They are still
     * accepted, but only when they name exactly one product.
     *
     * @param  array<int, string>  $keys
     * @return array<string, Product|string>
     */
    private function resolve(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $found = [];
        $linkedCodes = [];
        $linkedProductIds = [];

        if (Schema::hasTable('part_numbers') && Schema::hasTable('product_part_numbers')) {
            $lookupKeys = array_values(array_unique(array_merge(
                $keys,
                array_map(fn (string $key): string => $this->compactKey($key), $keys),
            )));

            DB::table('product_part_numbers as product_numbers')
                ->join('part_numbers as numbers', 'numbers.id', '=', 'product_numbers.part_number_id')
                ->whereNull('numbers.deleted_at')
                ->where(function ($query) use ($lookupKeys): void {
                    $query
                        ->whereIn(DB::raw('LOWER(TRIM(numbers.number))'), $lookupKeys)
                        ->orWhereIn(DB::raw('LOWER(TRIM(numbers.normalized_number))'), $lookupKeys);
                })
                ->get(['product_numbers.product_id', 'numbers.number', 'numbers.normalized_number'])
                ->each(function (object $number) use ($keys, &$linkedCodes, &$linkedProductIds): void {
                    $productId = (int) $number->product_id;

                    foreach ([(string) $number->number, (string) $number->normalized_number] as $candidate) {
                        $strict = $this->key($candidate);
                        $compact = $this->compactKey($candidate);

                        foreach ($keys as $requested) {
                            if ($strict === $requested || ($compact !== '' && $compact === $this->compactKey($requested))) {
                                $linkedCodes[$productId][$requested] = true;
                                $linkedProductIds[$productId] = true;
                            }
                        }
                    }
                });
        }

        Product::query()
            ->select(['id', 'sku', 'part_number', 'oem_number', 'stock_quantity', 'name_en', 'name_ar', 'name_ku'])
            ->where(function ($query) use ($keys, $linkedProductIds): void {
                $query
                    ->whereIn(DB::raw('LOWER(TRIM(sku))'), $keys)
                    ->orWhereIn(DB::raw('LOWER(TRIM(part_number))'), $keys)
                    ->orWhereIn(DB::raw('LOWER(TRIM(oem_number))'), $keys);

                if ($linkedProductIds !== []) {
                    $query->orWhereIn('id', array_keys($linkedProductIds));
                }
            })
            ->get()
            ->each(function (Product $product) use ($keys, $linkedCodes, &$found): void {
                $sku = $this->key((string) $product->sku);

                if (in_array($sku, $keys, true)) {
                    // An exact SKU beats anything the same string matched
                    // through the looser columns.
                    $found[$sku] = ['exact' => $product, 'loose' => $found[$sku]['loose'] ?? collect()];
                }

                foreach ([(string) $product->part_number, (string) $product->oem_number] as $alternate) {
                    $key = $this->key($alternate);

                    if ($key !== '' && in_array($key, $keys, true)) {
                        $found[$key]['loose'] = ($found[$key]['loose'] ?? collect())->push($product);
                    }
                }

                foreach (array_keys($linkedCodes[(int) $product->id] ?? []) as $key) {
                    $found[$key]['loose'] = ($found[$key]['loose'] ?? collect())->push($product);
                }
            });

        $resolved = [];

        foreach ($keys as $key) {
            $exact = $found[$key]['exact'] ?? null;

            if ($exact instanceof Product) {
                $resolved[$key] = $exact;

                continue;
            }

            /** @var Collection<int, Product> $loose */
            $loose = $found[$key]['loose'] ?? collect();
            $unique = $loose->unique('id');

            $resolved[$key] = match (true) {
                $unique->count() === 1 => $unique->first(),
                $unique->count() > 1 => 'ambiguous',
                default => null,
            };
        }

        return array_filter($resolved, static fn ($value): bool => $value !== null);
    }

    private function key(string $code): string
    {
        // Codes copied from RTL screens can carry invisible direction marks;
        // spreadsheet cells often carry non-breaking spaces. They look
        // identical in the review table but prevent an exact DB match unless
        // removed here.
        $clean = preg_replace('/[\p{Cf}\x{00A0}\x{2007}\x{202F}]/u', '', $code) ?? $code;

        return mb_strtolower(trim($clean));
    }

    private function compactKey(string $code): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $this->key($code)) ?? '';
    }
}
