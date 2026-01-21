<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;
use Webard\Biloquent\Concerns\HasConditions;
use Webard\Biloquent\Concerns\HasLabel;
use Webard\Biloquent\Contracts\Aggregator as AggregatorContract;

/**
 * Raw aggregator for custom SQL expressions.
 *
 * Use this as an escape hatch when the built-in aggregators
 * don't support your use case.
 *
 * @phpstan-consistent-constructor
 */
class Raw implements AggregatorContract
{
    use HasConditions;
    use HasLabel;

    protected Expression|string|null $selectExpression = null;

    protected Expression|string|null $groupByExpression = null;

    /**
     * @var array<string>
     */
    protected array $datasetColumns = [];

    protected ?Closure $datasetCallback = null;

    public function __construct(
        protected string $name
    ) {}

    /**
     * Create a new raw aggregator instance.
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
     * Set the SELECT expression.
     */
    public function select(Expression|string $expression): static
    {
        $this->selectExpression = $expression;

        return $this;
    }

    /**
     * Set a GROUP BY expression.
     */
    public function groupBy(Expression|string $expression): static
    {
        $this->groupByExpression = $expression;

        return $this;
    }

    /**
     * Add columns to be selected in the dataset.
     *
     * @param  array<string>|string  $columns
     */
    public function datasetColumns(array|string $columns): static
    {
        $this->datasetColumns = is_array($columns) ? $columns : [$columns];

        return $this;
    }

    /**
     * Apply a custom callback to the dataset query.
     */
    public function dataset(Closure $callback): static
    {
        $this->datasetCallback = $callback;

        return $this;
    }

    /**
     * Convert this aggregator to an SQL expression.
     */
    public function toExpression(Grammar $grammar): Expression
    {
        if ($this->selectExpression === null) {
            throw new \RuntimeException("Raw aggregator '{$this->name}' requires a select expression.");
        }

        $expression = $this->selectExpression instanceof Expression
            ? $this->selectExpression
            : DB::raw($this->selectExpression);

        return new Alias($expression, $this->getAlias());
    }

    /**
     * Apply any necessary modifications to the dataset query.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $dataset
     */
    public function applyToDataset(Builder $dataset): void
    {
        if ($this->datasetCallback !== null) {
            ($this->datasetCallback)($dataset);
        }
    }

    /**
     * Get the columns that need to be selected in the dataset.
     *
     * @return array<string>
     */
    public function getDatasetColumns(): array
    {
        return $this->datasetColumns;
    }

    /**
     * Get the GROUP BY expression if set.
     */
    public function getGroupByExpression(): Expression|string|null
    {
        return $this->groupByExpression;
    }
}
