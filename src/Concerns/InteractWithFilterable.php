<?php

namespace YSM\Filterable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use YSM\Filterable\Filterable;

/**
 * @method static Builder filterable(Filterable $filter)
 */
trait InteractWithFilterable
{
    /**
     * Apply all relevant filters.
     *
     * @param Builder    $query
     * @param Filterable $filter
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterable(Builder $query, Filterable $filter): \Illuminate\Database\Eloquent\Builder
    {
        return $filter->apply($query);
    }
}
