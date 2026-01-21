<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Webard\Biloquent\Expressions\Kurtosis as KurtosisExpression;

/**
 * Kurtosis aggregator.
 *
 * Only supported on MySQL/MariaDB with UDF Infusion extension.
 *
 * @see https://github.com/infusion/udf_infusion
 */
class Kurtosis extends Aggregator
{
    /**
     * Build the KURTOSIS expression.
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        return new KurtosisExpression($column);
    }
}
