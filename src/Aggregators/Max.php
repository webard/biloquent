<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Tpetry\QueryExpressions\Function\Aggregate\Max as TpetryMax;

class Max extends Aggregator
{
    /**
     * Build the MAX expression.
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        return new TpetryMax($column);
    }
}
