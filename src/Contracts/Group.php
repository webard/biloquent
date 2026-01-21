<?php

declare(strict_types=1);

namespace Webard\Biloquent\Contracts;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

interface Group
{
    /**
     * Create a new group instance.
     */
    public static function make(string $name): static;

    /**
     * Get the name of this group.
     */
    public function getName(): string;

    /**
     * Get the alias for the result column.
     */
    public function getAlias(): string;

    /**
     * Get the label for display purposes.
     */
    public function getLabel(): ?string;

    /**
     * Check if this group should be included.
     */
    public function isVisible(): bool;

    /**
     * Convert this group to an SQL expression for SELECT.
     */
    public function toSelectExpression(Grammar $grammar): Expression;

    /**
     * Convert this group to an SQL expression for GROUP BY.
     */
    public function toGroupByExpression(Grammar $grammar): Expression;

    /**
     * Apply any necessary modifications to the dataset query.
     * This is called before the CTE is built.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $dataset
     */
    public function applyToDataset(Builder $dataset): void;

    /**
     * Apply any necessary joins to the report query.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function applyJoins(Builder $query): void;

    /**
     * Get the columns that need to be selected in the dataset.
     *
     * @return array<string>
     */
    public function getDatasetColumns(): array;
}
