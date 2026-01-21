<?php

declare(strict_types=1);

namespace Webard\Biloquent\Groups;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Webard\Biloquent\Expressions\ExtractDate;
use Webard\Biloquent\Expressions\ExtractDay;
use Webard\Biloquent\Expressions\ExtractMonth;
use Webard\Biloquent\Expressions\ExtractYear;

/**
 * Group by a date/time extraction (year, month, day, date).
 */
class DateGroup extends Group
{
    protected string $extraction = 'date';

    /**
     * Set the date part to extract.
     *
     * @param  'year'|'month'|'day'|'date'  $part
     */
    public function extract(string $part): static
    {
        $this->extraction = $part;

        return $this;
    }

    /**
     * Extract the year from the date.
     */
    public function year(): static
    {
        return $this->extract('year');
    }

    /**
     * Extract the month from the date.
     */
    public function month(): static
    {
        return $this->extract('month');
    }

    /**
     * Extract the day from the date.
     */
    public function day(): static
    {
        return $this->extract('day');
    }

    /**
     * Extract the date (without time) from the datetime.
     */
    public function date(): static
    {
        return $this->extract('date');
    }

    /**
     * Build the group expression.
     */
    protected function buildExpression(Grammar $grammar): Expression
    {
        $column = $this->getColumnAlias();

        return match ($this->extraction) {
            'year' => new ExtractYear($column),
            'month' => new ExtractMonth($column),
            'day' => new ExtractDay($column),
            'date' => new ExtractDate($column),
            default => throw new \InvalidArgumentException("Unknown date extraction: {$this->extraction}"),
        };
    }
}
