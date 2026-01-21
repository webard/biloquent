<?php

declare(strict_types=1);

namespace Webard\Biloquent\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Webard\Biloquent\Exceptions\UnsupportedAggregatorException;

/**
 * Skewness aggregate function.
 *
 * Only supported on MySQL/MariaDB with UDF Infusion extension.
 *
 * @see https://github.com/infusion/udf_infusion
 */
class Skewness implements Expression
{
    public function __construct(
        protected string|Expression $column
    ) {}

    public function getValue(Grammar $grammar): string
    {
        if (! $grammar instanceof MySqlGrammar) {
            throw UnsupportedAggregatorException::forDriver(
                'Skewness',
                $this->getDriverName($grammar),
                'Skewness is only available on MySQL/MariaDB with UDF Infusion extension.'
            );
        }

        $column = $this->column instanceof Expression
            ? $this->column->getValue($grammar)
            : $grammar->wrap($this->column);

        return "skewness({$column})";
    }

    protected function getDriverName(Grammar $grammar): string
    {
        return match (true) {
            str_contains($grammar::class, 'Postgres') => 'PostgreSQL',
            str_contains($grammar::class, 'SQLite') => 'SQLite',
            str_contains($grammar::class, 'SqlServer') => 'SQL Server',
            default => 'unknown',
        };
    }
}
