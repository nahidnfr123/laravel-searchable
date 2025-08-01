<?php

namespace NahidFerdous\Searchable;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait Searchable
{
    public function scopeSearch(Builder $query, ?string $term = null, array $attributes = []): Builder
    {
        $term = $term ?? request(config('searchable.key', 'q'));
        $attributes = $attributes ?: $this->searchableAttributes();

        if (
            !$term
            || empty($attributes)
            || strlen($term) < config('searchable.min_search_length', 1)
        ) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term, $attributes) {
            foreach ($attributes as $attribute) {
                $this->applySearchCondition($query, $term, $attribute);
            }
        });
    }

    public function scopeSearchDate(Builder $query, ?string $term = null, array $attributes = [], string $operator = '='): Builder
    {
        $term = $term ?? request(config('searchable.key', 'q'));

        if (!$term || empty($attributes)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term, $attributes, $operator) {
            foreach ($attributes as $attribute) {
                $this->applyDateCondition($query, $term, $attribute, $operator);
            }
        });
    }

    protected function applySearchCondition(Builder $query, string $term, string $attribute): void
    {
        if (str_contains($attribute, '|')) {
            [$relation, $column] = explode('|', $attribute);
            $query->orWhereHas($relation, function (Builder $q) use ($term, $column) {
                $this->applyColumnCondition($q, $term, $column);
            });
        } else {
            $this->applyColumnCondition($query, $term, $attribute);
        }
    }

    protected function applyDateCondition(Builder $query, string $term, string $attribute, string $operator): void
    {
        if (str_contains($attribute, '|>')) {
            [$relation, $column] = explode('|>', $attribute);
            $query->whereHas($relation, function (Builder $q) use ($term, $column) {
                $this->applyColumnCondition($q, $term, $column);
            });
        } elseif (str_contains($attribute, '|')) {
            [$relation, $column] = explode('|', $attribute);
            $query->orWhereHas($relation, function (Builder $q) use ($term, $column) {
                $this->applyColumnCondition($q, $term, $column);
            });
        } else {
            $this->applyColumnCondition($query, $term, $attribute);
        }
    }

    protected function applyColumnCondition(Builder $query, string $term, string $column): void
    {
        $caseSensitive = config('searchable.case_sensitive', false);
        $fulltext = config('searchable.fulltext', false);
        $operatorMap = config('searchable.operator_map', []);

        $operator = config('searchable.operator', 'LIKE');
        $value = $term;

        $prefix = $term[0] ?? null;

        if (array_key_exists($prefix, $operatorMap)) {
            $operator = $operatorMap[$prefix];
            $value = substr($term, 1);
        }

        if (Str::contains($column, '+')) {
            $columns = explode('+', $column);
            $concat = implode(", ' ', ", $columns);
            $query->orWhere(DB::raw("CONCAT({$concat})"), 'LIKE', "%{$value}%");
            return;
        }

        if ($operator === '~' && $fulltext) {
            $query->orWhereRaw("MATCH ({$column}) AGAINST (? IN BOOLEAN MODE)", [$value]);
            return;
        }

        if ($operator === '@') {
            $query->orWhereJsonContains($column, $value);
            return;
        }

        if ($operator === '%') {
            $query->orWhere($column, 'LIKE', "%{$value}%");
            return;
        }

        if ($operator === '^') {
            $query->orWhere($column, 'LIKE', "{$value}%");
            return;
        }

        if ($operator === '$') {
            $query->orWhere($column, 'LIKE', "%{$value}");
            return;
        }

        // Case-insensitive fallback
        if (!$caseSensitive && in_array($operator, ['LIKE', '=', '!='])) {
            $query->orWhereRaw("LOWER({$column}) {$operator} ?", [Str::lower($value)]);
        } else {
            $query->orWhere($column, $operator, $value);
        }
    }

    protected function applyDateComparison(Builder $query, string $term, string $column, string $operator): void
    {
        $query->orWhere(function (Builder $q) use ($term, $column, $operator) {
            if (str_contains($term, ' - ')) {
                [$start, $end] = array_map([Carbon::class, 'parse'], explode(' - ', $term));
                $q->whereBetween($column, [$start, $end]);
            } else {
                $q->whereDate($column, $operator, $term);
            }
        });
    }

    public function searchableAttributes(): array
    {
        if (method_exists($this, 'searchable')) {
            return $this->searchable();
        }

        return property_exists($this, 'searchable') ? $this->searchable : [];
    }
}
