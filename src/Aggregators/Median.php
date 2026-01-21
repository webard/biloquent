<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Webard\Biloquent\Expressions\Median as MedianExpression;

class Median extends Aggregator
{
    /**
     * Build the MEDIAN expression.
     *
     * Note: This requires database-specific support:
     * - MySQL/MariaDB: UDF Infusion extension
     * - PostgreSQL: Native percentile_cont(0.5)
     * - SQLite: Compiled with SQLITE_ENABLE_PERCENTILE
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        return new MedianExpression($column);
    }
}
