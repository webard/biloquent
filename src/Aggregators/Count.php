<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Tpetry\QueryExpressions\Function\Aggregate\Count as TpetryCount;

class Count extends Aggregator
{
    protected bool $distinct = false;

    /**
     * Count distinct values only.
     */
    public function distinct(bool $distinct = true): static
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Build the COUNT expression.
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        // If we have a filter, use the filtered marker column
        if ($this->hasFilter()) {
            $column = $this->getColumnAlias().'_filtered';
        }

        return new TpetryCount($column, $this->distinct);
    }
}
