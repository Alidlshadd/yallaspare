<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class SqlSafe
{
    public static function searchTerm(mixed $value, int $maxLength = 120): string
    {
        $term = trim((string) $value);

        return mb_substr($term, 0, $maxLength);
    }

    public static function containsPattern(mixed $value, int $maxLength = 120): string
    {
        $term = self::searchTerm($value, $maxLength);
        $term = str_replace('\\', '\\\\', $term);
        $term = str_replace('%', '\%', $term);
        $term = str_replace('_', '\_', $term);

        return '%' . $term . '%';
    }

    public static function whereLike(EloquentBuilder|QueryBuilder $query, string $column, mixed $value, string $boolean = 'and'): void
    {
        $grammar = DB::connection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);

        $query->whereRaw(
            $wrappedColumn . " LIKE ? ESCAPE '\\'",
            [self::containsPattern($value)],
            $boolean
        );
    }

    public static function orWhereLike(EloquentBuilder|QueryBuilder $query, string $column, mixed $value): void
    {
        self::whereLike($query, $column, $value, 'or');
    }
}
