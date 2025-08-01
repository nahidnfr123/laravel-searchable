<?php

namespace NahidFerdous\Searchable;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait SearchableBackup
{
    /**
     * Scope a query to search for term in the attributes
     *
     * @param Builder $query
     * @return Builder
     */
    protected function scopeSearch(Builder $query): Builder
    {
        [$searchTerm, $attributes] = $this->parseArguments(func_get_args());

        if (!isset($searchTerm) || !$attributes) { // Check $searchTerm by isset() instead of empty() because $searchTerm can be 0
            return $query;
        }

        return $query->where(function (Builder $query) use ($searchTerm, $attributes) {
            foreach ($attributes as $attribute) {
                $query->when(
                    str_contains($attribute, '|'),
                    function (Builder $query) use ($searchTerm, $attribute) {
                        [$relationName, $relationAttribute] = $this->relationName($attribute);

                        $query->when(
                            str_contains($relationAttribute, ','),
                            function (Builder $query) use ($searchTerm, $relationName, $relationAttribute) {
                                $relationAttributes = explode(',', $relationAttribute);
                                $query->orWhereHas($relationName, function (Builder $query) use ($searchTerm, $relationAttributes) {
                                    $query->where(function (Builder $query) use ($searchTerm, $relationAttributes) {
                                        foreach ($relationAttributes as $relationAttribute) {
                                            if (str_contains($relationAttribute, '%')) {
                                                $relationAttribute = str_replace('%', '', $relationAttribute);
                                                if (str_contains($relationAttribute, '+')) {
                                                    $relationAttributes = explode('+', $relationAttribute);
//                                                    $query->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$searchTerm.'%']);
                                                    $query->orWhere(DB::raw("concat($relationAttributes[0], ' ', $relationAttributes[1])"), 'LIKE', "%" . $searchTerm . "%");
                                                } else {
                                                    $query->orWhere($relationAttribute, 'LIKE', "%{$searchTerm}%");
                                                }
                                            } else {
                                                $query->orWhere($relationAttribute, $searchTerm);
                                            }
                                        }
                                    });
                                });
                            },
                            function (Builder $query) use ($searchTerm, $relationName, $relationAttribute) {
                                $query->orWhereHas($relationName, function (Builder $query) use ($searchTerm, $relationAttribute) {
                                    if (str_contains($relationAttribute, '%')) {
                                        $relationAttribute = str_replace('%', '', $relationAttribute);
                                        if (str_contains($relationAttribute, '+')) {
                                            $relationAttributes = explode('+', $relationAttribute);
                                            $query->where(DB::raw("concat($relationAttributes[0], ' ', $relationAttributes[1])"), 'LIKE', "%" . $searchTerm . "%");
                                        } else {
                                            $query->where($relationAttribute, 'LIKE', "%{$searchTerm}%");
                                        }
                                    } else {
                                        $query->where($relationAttribute, $searchTerm);
                                    }
                                });
                            });
                    },
                    function (Builder $query) use ($searchTerm, $attribute) {
                        if (str_contains($attribute, '%')) {
                            $attribute = str_replace('%', '', $attribute);
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        } else {
                            $query->orWhere($attribute, $searchTerm);
                        }
                    }
                );
            }
        });
    }

    /**
     * Extract Relation Name and Relation Attribute from the attribute
     *
     * @param $attribute
     * @return array
     */
    protected function relationName($attribute): array
    {
        $relationName = '';
        $relationAttribute = '';
        $array = explode('|', $attribute);
        if (count($array) > 1) {
            $relationName = Arr::first($array);
            $relationAttribute = Arr::last($array);
        }
        return [$relationName, $relationAttribute];
    }

    /**
     * Scope a query to search for term in the attributes
     *
     * @param Builder $query
     * @return Builder
     */
    protected function scopeSearchDate(Builder $query): Builder
    {
        [$searchTerm, $attributes, $operator] = $this->parseArguments(func_get_args());
        if (!$searchTerm || !$attributes || !$operator) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($searchTerm, $attributes, $operator) {
            foreach ($attributes as $attribute) {
                $query->when(
                    str_contains($attribute, '|'),
                    function (Builder $query) use ($searchTerm, $attribute, $operator) {
                        [$relationName, $relationAttribute] = explode('.', $attribute);

                        $query->when(
                            str_contains($relationAttribute, ','),
                            function (Builder $query) use ($searchTerm, $relationName, $relationAttribute, $operator) {
                                $relationAttributes = explode(',', $relationAttribute);
                                $query->orWhereHas($relationName, function (Builder $query) use ($searchTerm, $relationAttributes, $operator) {
                                    $query->where(function (Builder $query) use ($searchTerm, $relationAttributes, $operator) {
                                        foreach ($relationAttributes as $relationAttribute) {
                                            if (str_contains($searchTerm, ' - ')) {
                                                $searchTerm = explode(' - ', $searchTerm);
                                                $start = Carbon::parse($searchTerm[0]);
                                                $end = Carbon::parse($searchTerm[1]);
                                                $query->orWhereBetween($relationAttribute, [$start, $end]);
                                            } else {
                                                $query->orWhereDate($relationAttribute, $operator, $searchTerm);
                                            }
                                        }
                                    });
                                });
                            },
                            function (Builder $query) use ($searchTerm, $relationName, $relationAttribute, $operator) {
                                $query->orWhereHas($relationName, function (Builder $query) use ($searchTerm, $relationAttribute, $operator) {
                                    if (str_contains($searchTerm, ' - ')) {
                                        $searchTerm = explode(' - ', $searchTerm);
                                        $start = Carbon::parse($searchTerm[0]);
                                        $end = Carbon::parse($searchTerm[1]);
                                        $query->orWhereBetween($relationAttribute, [$start, $end]);
                                    } else {
                                        $query->orWhereDate($relationAttribute, $operator, $searchTerm);
                                    }
                                });
                            });
                    },
                    function (Builder $query) use ($searchTerm, $attribute, $operator) {
                        $query->orWhere(function ($query) use ($searchTerm, $attribute, $operator) {
                            if (str_contains($searchTerm, ' - ')) {
                                $searchTerm = explode(' - ', $searchTerm);
                                $start = Carbon::parse($searchTerm[0]);
                                $end = Carbon::parse($searchTerm[1]);
                                $query->whereBetween($attribute, [$start, $end]);
                            } else {
                                $query->whereDate($attribute, $operator, $searchTerm);
                            }
                        });
                    }
                );
            }
        });
    }


    /**
     * Parse search scope arguments
     *
     * @param array $arguments
     * @return array
     */
    private function parseArguments(array $arguments): array
    {
        $args_count = count($arguments);

        return match ($args_count) {
            1 => [request(config('searchable.key')), $this->searchableAttributes()],
            2 => is_string($arguments[1])
                ? [$arguments[1], $this->searchableAttributes()]
                : [request(config('searchable.key')), $arguments[1]],
            3 => is_string($arguments[1])
                ? [$arguments[1], $arguments[2]]
                : [$arguments[2], $arguments[1]],
            4 => is_string($arguments[1])
                ? [$arguments[1], $arguments[2], $arguments[3]]
                : [$arguments[2], $arguments[1], $arguments[3]],
            default => [null, []],
        };
    }

    /**
     * Get searchable columns
     *
     * @return array
     */
    public function searchableAttributes(): array
    {
        if (method_exists($this, 'searchable')) {
            return $this->searchable();
        }

        return property_exists($this, 'searchable') ? $this->searchable : [];
    }
}
