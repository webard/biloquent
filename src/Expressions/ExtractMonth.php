<?php

declare(strict_types=1);

namespace Webard\Biloquent\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

class ExtractMonth implements Expression
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
            $grammar instanceof MySqlGrammar => "MONTH({$column})",
            $grammar instanceof PostgresGrammar => "EXTRACT(MONTH FROM {$column})::INTEGER",
            $grammar instanceof SQLiteGrammar => "CAST(strftime('%m', {$column}) AS INTEGER)",
            default => "EXTRACT(MONTH FROM {$column})",
        };
    }
}
