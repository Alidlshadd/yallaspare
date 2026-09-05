<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

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
        $term = str_replace('!', '!!', $term);
        $term = str_replace('%', '!%', $term);
        $term = str_replace('_', '!_', $term);

        return '%'.$term.'%';
    }

    /**
     * Anchored at the start, for ranking a name the shopper began typing.
     */
    public static function startsWithPattern(mixed $value, int $maxLength = 120): string
    {
        $term = self::searchTerm($value, $maxLength);
        $term = str_replace('!', '!!', $term);
        $term = str_replace('%', '!%', $term);
        $term = str_replace('_', '!_', $term);

        return $term.'%';
    }

    public static function whereLike(EloquentBuilder|QueryBuilder $query, string $column, mixed $value, string $boolean = 'and'): void
    {
        $connection = $query instanceof EloquentBuilder
            ? $query->getQuery()->getConnection()
            : $query->getConnection();
        $grammar = $connection->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);

        $query->whereRaw(
            $wrappedColumn." LIKE ? ESCAPE '!'",
            [self::containsPattern($value)],
            $boolean
        );
    }

    public static function orWhereLike(EloquentBuilder|QueryBuilder $query, string $column, mixed $value): void
    {
        self::whereLike($query, $column, $value, 'or');
    }

    /**
     * Match a part number however either side punctuates it.
     *
     * "SY-1721840025" and "SY1721840025" are one number to a person and two
     * strings to a LIKE, and the dash may be in the query, in the column, or in
     * neither. Stripping the separators from both sides is the only way to make
     * all four combinations meet.
     *
     * The column is wrapped by the grammar and the value is bound; nothing here
     * is concatenated into SQL. It does cost a scan of that column, so it is
     * meant for the short identifier fields and for tokens that look like a
     * part number — not for names or descriptions.
     */
    public static function orWhereLikeIgnoringPunctuation(EloquentBuilder|QueryBuilder $query, string $column, mixed $value): void
    {
        $bare = preg_replace('/[^\p{L}\p{N}]+/u', '', self::searchTerm($value));

        if ($bare === null || $bare === '') {
            return;
        }

        $grammar = $query instanceof EloquentBuilder
            ? $query->getQuery()->getGrammar()
            : $query->getGrammar();

        $expression = $grammar->wrap($column);

        // The separators a part number is actually written with.
        foreach (['-', ' ', '.', '/', '_'] as $separator) {
            $expression = "REPLACE({$expression}, ?, '')";
        }

        $bindings = ['-', ' ', '.', '/', '_'];
        $bindings[] = self::containsPattern($bare);

        $query->whereRaw($expression." LIKE ? ESCAPE '!'", $bindings, 'or');
    }
}
