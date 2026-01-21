<?php

declare(strict_types=1);

namespace Webard\Biloquent\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

trait HasFilter
{
    protected ?Closure $filter = null;

    /**
     * Set a filter condition for this aggregator.
     * Only rows matching this condition will be included in the aggregation.
     */
    public function filter(Closure $callback): static
    {
        $this->filter = $callback;

        return $this;
    }

    /**
     * Check if this aggregator has a filter.
     */
    public function hasFilter(): bool
    {
        return $this->filter !== null;
    }

    /**
     * Get the filter closure.
     */
    public function getFilter(): ?Closure
    {
        return $this->filter;
    }

    /**
     * Apply the filter to a query builder.
     */
    public function applyFilter(Builder $query): Builder
    {
        if ($this->filter !== null) {
            ($this->filter)($query);
        }

        return $query;
    }
}
