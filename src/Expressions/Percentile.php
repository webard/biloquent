<?php

declare(strict_types=1);

namespace Webard\Biloquent\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Webard\Biloquent\Exceptions\UnsupportedAggregatorException;
use Webard\Biloquent\Support\SqliteFeatureDetector;

class Percentile implements Expression
{
    public function __construct(
        protected string|Expression $column,
        protected float $percentile = 0.5
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $column = $this->column instanceof Expression
            ? $this->column->getValue($grammar)
            : $grammar->wrap($this->column);

        $percentile = $this->percentile;

        return match (true) {
            // MySQL requires UDF Infusion extension
            // UDF uses: percentile_cont(column, fraction)
            $grammar instanceof MySqlGrammar => "percentile_cont({$column}, {$percentile})",

            // PostgreSQL uses percentile_cont as ordered-set aggregate
            $grammar instanceof PostgresGrammar => "percentile_cont({$percentile}) WITHIN GROUP (ORDER BY {$column})",

            // SQLite requires compile flag SQLITE_ENABLE_PERCENTILE
            $grammar instanceof SQLiteGrammar => $this->getSqliteExpression((string) $column, $percentile),

            default => throw UnsupportedAggregatorException::forDriver('Percentile', 'unknown'),
        };
    }

    protected function getSqliteExpression(string $column, float $percentile): string
    {
        if (! SqliteFeatureDetector::hasPercentile()) {
            throw UnsupportedAggregatorException::forDriver(
                'Percentile',
                'SQLite',
                'SQLite must be compiled with SQLITE_ENABLE_PERCENTILE flag.'
            );
        }

        // SQLite percentile_cont uses 0-1 range like PostgreSQL
        return "percentile_cont({$column}, {$percentile})";
    }
}
