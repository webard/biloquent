<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Webard\Biloquent\Expressions\Percentile as PercentileExpression;

class Percentile extends Aggregator
{
    protected float $percentile = 0.5;

    /**
     * Set the percentile value (0.0 to 1.0).
     */
    public function percentile(float $percentile): static
    {
        $this->percentile = $percentile;

        return $this;
    }

    /**
     * Alias for percentile() - set the fraction.
     */
    public function fraction(float $fraction): static
    {
        return $this->percentile($fraction);
    }

    /**
     * Build the PERCENTILE expression.
     *
     * Note: This requires database-specific support:
     * - MySQL/MariaDB: UDF Infusion extension
     * - PostgreSQL: Native percentile_cont
     * - SQLite: Compiled with SQLITE_ENABLE_PERCENTILE
     */
    protected function buildAggregateExpression(Grammar $grammar): Expression
    {
        $column = $this->getAggregateColumn();

        return new PercentileExpression($column, $this->percentile);
    }
}
