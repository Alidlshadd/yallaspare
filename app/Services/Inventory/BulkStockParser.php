<?php

namespace App\Services\Inventory;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Turns what an administrator pasted or uploaded into rows.
 *
 * Pasting and uploading arrive here together on purpose. Written as two
 * readers they would agree today and drift apart by the third change, and the
 * one that quietly accepted something the other rejected would be found by a
 * stock count months later.
 *
 * Nothing here touches the database. It reads text and reports what it could
 * and could not make sense of; deciding whether a row can actually be applied
 * is BulkStockAdjustment's job.
 */
class BulkStockParser
{
    /**
     * More than this in one go and the request is refused.
     *
     * A single transaction holding thousands of product rows locks the table
     * for as long as it takes, and customers cannot check out while it does.
     */
    public const MAX_ROWS = 1000;

    /**
     * The columns this reads, old spelling and new.
     *
     * The new form says what it means: one signed number, plus or minus. The
     * old three-column form is still accepted because files in that shape
     * already exist and there is no reason to break them.
     */
    private const SKU_HEADERS = ['sku', 'product_sku'];

    private const CHANGE_HEADERS = ['quantity_change', 'change', 'qty_change'];

    /**
     * @return array{rows: array<int, array{sku: string, change: int, reference: ?string, note: ?string, line: int}>, errors: array<int, array{line: int, raw: string, message: string}>}
     */
    public function fromText(string $text): array
    {
        $rows = [];
        $errors = [];
        $line = 0;

        foreach (preg_split('/\R/', $text) ?: [] as $raw) {
            $line++;
            $trimmed = trim((string) $raw);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (count($rows) + count($errors) >= self::MAX_ROWS) {
                $errors[] = $this->error($line, $trimmed, __('Only :max rows can be adjusted at once. Split the list and run it again.', ['max' => self::MAX_ROWS]));
                break;
            }

            // One separator or several, comma, semicolon, tab or spaces — an
            // administrator pasting from a spreadsheet should not have to know
            // which one this expects.
            $parts = preg_split('/[\s,;]+/', $trimmed) ?: [];
            $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

            if (count($parts) < 2) {
                $errors[] = $this->error($line, $trimmed, __('Write a code and a change, like :example.', ['example' => 'ABC123 +20']));

                continue;
            }

            $sku = (string) $parts[0];
            $change = $this->toChange((string) $parts[1]);

            if ($change === null) {
                $errors[] = $this->error($line, $trimmed, __('":value" is not a whole number of units.', ['value' => $parts[1]]));

                continue;
            }

            if ($change === 0) {
                $errors[] = $this->error($line, $trimmed, __('A change of zero does nothing. Remove the row or correct the number.'));

                continue;
            }

            $rows[] = [
                'sku' => $sku,
                'change' => $change,
                'reference' => null,
                // Anything after the number is treated as a note rather than
                // discarded, so "ABC123 -2 broken in transit" keeps its reason.
                'note' => count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null,
                'line' => $line,
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * @return array{rows: array<int, array{sku: string, change: int, reference: ?string, note: ?string, line: int}>, errors: array<int, array{line: int, raw: string, message: string}>}
     */
    public function fromFile(UploadedFile $file): array
    {
        $sheets = Excel::toArray(null, $file);
        $grid = $sheets[0] ?? [];

        if ($grid === []) {
            return ['rows' => [], 'errors' => [$this->error(1, '', __('The file is empty.'))]];
        }

        $headers = array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) array_shift($grid)
        );

        $skuColumn = $this->columnFor($headers, self::SKU_HEADERS);
        $changeColumn = $this->columnFor($headers, self::CHANGE_HEADERS);
        $typeColumn = $this->columnFor($headers, ['type']);
        $quantityColumn = $this->columnFor($headers, ['quantity']);

        if ($skuColumn === null) {
            return ['rows' => [], 'errors' => [$this->error(1, implode(',', $headers), __('The file needs a :column column.', ['column' => 'sku']))]];
        }

        // Either the signed column, or the old type + quantity pair.
        $legacy = $changeColumn === null && $typeColumn !== null && $quantityColumn !== null;

        if ($changeColumn === null && ! $legacy) {
            return ['rows' => [], 'errors' => [$this->error(1, implode(',', $headers), __('The file needs a :column column.', ['column' => 'quantity_change']))]];
        }

        $referenceColumn = $this->columnFor($headers, ['reference']);
        $noteColumn = $this->columnFor($headers, ['note']);

        $rows = [];
        $errors = [];
        $line = 1;

        foreach ($grid as $raw) {
            $line++;
            $cells = array_map(static fn ($value): string => trim((string) $value), (array) $raw);

            if (implode('', $cells) === '') {
                continue;
            }

            if (count($rows) + count($errors) >= self::MAX_ROWS) {
                $errors[] = $this->error($line, implode(',', $cells), __('Only :max rows can be adjusted at once. Split the file and run it again.', ['max' => self::MAX_ROWS]));
                break;
            }

            $sku = $cells[$skuColumn] ?? '';

            if ($sku === '') {
                $errors[] = $this->error($line, implode(',', $cells), __('This row has no product code.'));

                continue;
            }

            $change = $legacy
                ? $this->legacyChange($cells[$typeColumn] ?? '', $cells[$quantityColumn] ?? '')
                : $this->toChange($cells[$changeColumn] ?? '');

            if ($change === null) {
                $errors[] = $this->error($line, implode(',', $cells), $legacy
                    ? __('Type must be "in" or "out" and quantity a whole number.')
                    : __('":value" is not a whole number of units.', ['value' => $cells[$changeColumn] ?? '']));

                continue;
            }

            if ($change === 0) {
                $errors[] = $this->error($line, implode(',', $cells), __('A change of zero does nothing. Remove the row or correct the number.'));

                continue;
            }

            $rows[] = [
                'sku' => $sku,
                'change' => $change,
                'reference' => $referenceColumn !== null && ($cells[$referenceColumn] ?? '') !== '' ? $cells[$referenceColumn] : null,
                'note' => $noteColumn !== null && ($cells[$noteColumn] ?? '') !== '' ? $cells[$noteColumn] : null,
                'line' => $line,
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $candidates
     */
    private function columnFor(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $headers, true);

            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * A signed whole number, with or without its plus sign.
     */
    private function toChange(string $value): ?int
    {
        $value = str_replace([' ', '−'], ['', '-'], trim($value));

        if ($value === '' || preg_match('/^[+-]?\d+$/', $value) !== 1) {
            return null;
        }

        return (int) $value;
    }

    private function legacyChange(string $type, string $quantity): ?int
    {
        $type = strtolower(trim($type));
        $amount = $this->toChange($quantity);

        if ($amount === null || $amount < 0 || ! in_array($type, ['in', 'out'], true)) {
            return null;
        }

        return $type === 'in' ? $amount : -$amount;
    }

    /**
     * @return array{line: int, raw: string, message: string}
     */
    private function error(int $line, string $raw, string $message): array
    {
        return ['line' => $line, 'raw' => mb_substr($raw, 0, 120), 'message' => $message];
    }
}
