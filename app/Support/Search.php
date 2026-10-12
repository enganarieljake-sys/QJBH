<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Case-insensitive "contains" search over columns and related columns, the
 * same on MySQL, SQLite, and Postgres (where LIKE is case-sensitive).
 */
class Search
{
    /**
     * @param  list<string>  $columns  Plain columns ("full_name") or relation
     *                                 columns ("tenant.room.room_number").
     */
    public static function apply(Builder $query, ?string $term, array $columns): Builder
    {
        $term = Str::limit(trim((string) $term), 100, '');

        if ($term === '') {
            return $query;
        }

        // "!" as the escape character: a backslash means different things in MySQL string literals.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term));
        $like = "%{$escaped}%";
        $clause = fn (string $column) => ["LOWER({$column}) LIKE ? ESCAPE '!'", [$like]];

        return $query->where(function (Builder $query) use ($columns, $clause) {
            foreach ($columns as $column) {
                if (! str_contains($column, '.')) {
                    $query->orWhereRaw(...$clause($query->qualifyColumn($column)));

                    continue;
                }

                $relation = Str::beforeLast($column, '.');
                $field = Str::afterLast($column, '.');
                $query->orWhereHas($relation, fn (Builder $related) => $related->whereRaw(...$clause($related->qualifyColumn($field))));
            }
        });
    }
}
