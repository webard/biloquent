<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Tpetry\QueryExpressions\Function\Aggregate\Min as TpetryMin;

class Min extends Aggregator
{
    /**
     * Build the MIN expression.
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        return new TpetryMin($column);
    }
}
