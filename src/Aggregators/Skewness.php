<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Webard\Biloquent\Expressions\Skewness as SkewnessExpression;

/**
 * Skewness aggregator.
 *
 * Only supported on MySQL/MariaDB with UDF Infusion extension.
 *
 * @see https://github.com/infusion/udf_infusion
 */
class Skewness extends Aggregator
{
    /**
     * Build the SKEWNESS expression.
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        return new SkewnessExpression($column);
    }
}
