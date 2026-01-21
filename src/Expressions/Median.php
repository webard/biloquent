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

class Median implements Expression
{
    public function __construct(
        protected string|Expression $column
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $column = $this->column instanceof Expression
            ? $this->column->getValue($grammar)
            : $grammar->wrap($this->column);

        return match (true) {
            // MySQL requires UDF Infusion extension
            $grammar instanceof MySqlGrammar => "median({$column})",

            // PostgreSQL uses percentile_cont as ordered-set aggregate
            $grammar instanceof PostgresGrammar => "percentile_cont(0.5) WITHIN GROUP (ORDER BY {$column})",

            // SQLite requires compile flag SQLITE_ENABLE_PERCENTILE
            $grammar instanceof SQLiteGrammar => $this->getSqliteExpression((string) $column),

            default => throw UnsupportedAggregatorException::forDriver('Median', 'unknown'),
        };
    }

    protected function getSqliteExpression(string $column): string
    {
        if (! SqliteFeatureDetector::hasMedian()) {
            throw UnsupportedAggregatorException::forDriver(
                'Median',
                'SQLite',
                'SQLite must be compiled with SQLITE_ENABLE_PERCENTILE flag.'
            );
        }

        return "median({$column})";
    }
}
