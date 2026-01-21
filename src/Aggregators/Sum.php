<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Tpetry\QueryExpressions\Function\Aggregate\Sum as TpetrySum;

class Sum extends Aggregator
{
    /**
     * Build the SUM expression.
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        return new TpetrySum($column);
    }
}
