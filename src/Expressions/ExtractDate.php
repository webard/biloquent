<?php

declare(strict_types=1);

namespace Webard\Biloquent\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

class ExtractDate implements Expression
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
            $grammar instanceof MySqlGrammar => "DATE({$column})",
            $grammar instanceof PostgresGrammar => "{$column}::DATE",
            $grammar instanceof SQLiteGrammar => "DATE({$column})",
            default => "DATE({$column})",
        };
    }
}
