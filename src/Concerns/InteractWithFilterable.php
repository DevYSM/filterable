<?php

namespace YSM\Filterable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use YSM\Filterable\Filterable;

/**
 * @method static Builder filterable(Filterable|string $filter)
 */
trait InteractWithFilterable
{
    /**
     * Apply all relevant filters.
     *
     * @param Builder           $query
     * @param Filterable|string $filter
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function scopeFilterable(Builder $query, Filterable|string $filter): \Illuminate\Database\Eloquent\Builder
    {
        if (is_string($filter)) {
            $filter = app()->make($filter);
            if (!$filter instanceof Filterable) {
                throw new InvalidArgumentException("The provided filter class must implement the Filterable abstraction class.");
            }
        }

        return $filter->apply($query);
    }
}
