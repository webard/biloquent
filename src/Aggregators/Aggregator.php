<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Tpetry\QueryExpressions\Language\Alias;
use Webard\Biloquent\Concerns\HasColumn;
use Webard\Biloquent\Concerns\HasConditions;
use Webard\Biloquent\Concerns\HasFilter;
use Webard\Biloquent\Concerns\HasLabel;
use Webard\Biloquent\Concerns\HasRelation;
use Webard\Biloquent\Contracts\Aggregator as AggregatorContract;

abstract class Aggregator implements AggregatorContract
{
    use HasColumn;
    use HasConditions;
    use HasFilter;
    use HasLabel;
    use HasRelation;

    public function __construct(
        protected string $name
    ) {}

    /**
     * Create a new aggregator instance.
     */
    public static function make(string $name): static
    {
        return new static($name);
    }

    /**
     * Get the name/alias of this aggregator.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Convert this aggregator to an SQL expression.
     */
    public function toExpression(Grammar $grammar): Expression
    {
        $aggregateExpression = $this->buildAggregateExpression($grammar);

        return new Alias($aggregateExpression, $this->getAlias());
    }

    /**
     * Build the aggregate expression (without alias).
     * Must be implemented by each aggregator type.
     */
    abstract protected function buildAggregateExpression(Grammar $grammar): Expression;

    /**
     * Apply any necessary modifications to the dataset query.
     */
    public function applyToDataset(Builder $dataset): void
    {
        if ($this->hasRelation()) {
            $this->applyRelationToDataset($dataset);

            return;
        }

        // Default: just ensure the column is selected in the dataset
        // The actual selection is handled by getDatasetColumns()
    }

    /**
     * Apply relation aggregate to the dataset.
     */
    protected function applyRelationToDataset(Builder $dataset): void
    {
        $relationAggregator = $this->getRelationAggregator() ?? 'count';

        $dataset->withAggregate(
            $this->getRelation(),
            $this->getColumn(),
            $relationAggregator
        );
    }

    /**
     * Get the columns that need to be selected in the dataset.
     *
     * @return array<string>
     */
    public function getDatasetColumns(): array
    {
        if ($this->hasRelation()) {
            // Relation columns are handled by withAggregate()
            return [];
        }

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
     * Get the column reference to use in the aggregate function.
     * This is the alias from the CTE, not the original column.
     */
    protected function getAggregateColumn(): string
    {
        if ($this->hasRelation()) {
            $relation = $this->getRelation();
            $aggregator = $this->getRelationAggregator() ?? 'count';
            $column = $this->getColumn();

            // Laravel naming convention for withAggregate
            return "{$relation}_{$aggregator}_{$column}";
        }

        return $this->getColumnAlias();
    }
}
