<?php

declare(strict_types=1);

namespace Webard\Biloquent\Groups;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Tpetry\QueryExpressions\Language\Alias;
use Webard\Biloquent\Concerns\HasColumn;
use Webard\Biloquent\Concerns\HasConditions;
use Webard\Biloquent\Concerns\HasLabel;
use Webard\Biloquent\Contracts\Group as GroupContract;

abstract class Group implements GroupContract
{
    use HasColumn;
    use HasConditions;
    use HasLabel;

    public function __construct(
        protected string $name
    ) {}

    /**
     * Create a new group instance.
     */
    public static function make(string $name): static
    {
        return new static($name);
    }

    /**
     * Get the name of this group.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Convert this group to an SQL expression for SELECT.
     */
    public function toSelectExpression(Grammar $grammar): Expression
    {
        $expression = $this->buildExpression($grammar);

        return new Alias($expression, $this->getAlias());
    }

    /**
     * Convert this group to an SQL expression for GROUP BY.
     */
    public function toGroupByExpression(Grammar $grammar): Expression
    {
        return $this->buildExpression($grammar);
    }

    /**
     * Build the group expression.
     * Must be implemented by each group type.
     */
    abstract protected function buildExpression(Grammar $grammar): Expression;

    /**
     * Apply any necessary modifications to the dataset query.
     */
    public function applyToDataset(Builder $dataset): void
    {
        // Default: no modifications needed
    }

    /**
     * Apply any necessary joins to the report query.
     */
    public function applyJoins(Builder $query): void
    {
        // Default: no joins needed
    }

    /**
     * Get the columns that need to be selected in the dataset.
     *
     * @return array<string>
     */
    public function getDatasetColumns(): array
    {
        $column = $this->getColumn();

        if ($column === null) {
            return [];
        }

        if ($column instanceof Expression) {
            return [];
        }

        return [$column.' as '.$this->getColumnAlias()];
    }

    /**
     * Wrap a column name using the grammar.
     */
    protected function wrapColumn(Grammar $grammar, string|Expression $column): string
    {
        if ($column instanceof Expression) {
            return $column->getValue($grammar);
        }

        return $grammar->wrap($column);
    }
}
