<?php

declare(strict_types=1);

namespace Webard\Biloquent\Groups;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Facades\DB;

/**
 * Group by a simple column value.
 */
class ColumnGroup extends Group
{
    /**
     * Build the group expression.
     */
    protected function buildExpression(Grammar $grammar): Expression
    {
        $column = $this->getColumnAlias();

        return DB::raw($grammar->wrap($column));
    }
}
