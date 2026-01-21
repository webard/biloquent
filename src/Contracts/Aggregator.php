<?php

declare(strict_types=1);

namespace Webard\Biloquent\Contracts;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

interface Aggregator
{
    /**
     * Create a new aggregator instance.
     */
    public static function make(string $name): static;

    /**
     * Get the name/alias of this aggregator.
     */
    public function getName(): string;

    /**
     * Get the label for display purposes.
     */
    public function getLabel(): ?string;

    /**
     * Check if this aggregator should be included.
     */
    public function isVisible(): bool;

    /**
     * Convert this aggregator to an SQL expression.
     */
    public function toExpression(Grammar $grammar): Expression;

    /**
     * Apply any necessary modifications to the dataset query.
     * This is called before the CTE is built.
     */
    public function applyToDataset(Builder $dataset): void;

    /**
     * Get the columns that need to be selected in the dataset.
     *
     * @return array<string>
     */
    public function getDatasetColumns(): array;
}
