<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Contracts\Aggregator;
use Webard\Biloquent\Contracts\Group;

/**
 * Custom query builder for Biloquent reports.
 *
 * @template TModel of Report
 *
 * @extends Builder<TModel>
 *
 * @method $this withExpression(string $as, string|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|\Closure $query, array<string, string> $columns = [], bool $recursive = false, bool $materialized = null, int $cycle = null)
 */
class ReportBuilder extends Builder
{
    /**
     * The groups to apply to the query.
     *
     * @var array<string>
     */
    protected array $selectedGroups = [];

    /**
     * The aggregators to include in the query.
     *
     * @var array<string>
     */
    protected array $selectedAggregators = [];

    /**
     * Whether the query has been prepared.
     */
    protected bool $prepared = false;

    /**
     * Custom enhancer callback for post-processing results.
     */
    protected ?Closure $enhancer = null;

    /**
     * Set the groups to apply to the query.
     *
     * @param  string|array<string>  ...$groups
     * @return $this
     */
    public function grouping(string|array ...$groups): static
    {
        $this->selectedGroups = collect($groups)->flatten()->all();

        return $this;
    }

    /**
     * Set the aggregators to include in the query.
     *
     * @param  string|array<string>  ...$aggregators
     * @return $this
     */
    public function summary(string|array ...$aggregators): static
    {
        $this->selectedAggregators = collect($aggregators)->flatten()->all();

        return $this;
    }

    /**
     * Apply a custom callback to modify the dataset query.
     *
     * @return $this
     */
    public function enhance(Closure $callback): static
    {
        $callback($this->getDatasetQuery());

        return $this;
    }

    /**
     * Apply a callback for raw query access.
     *
     * @return $this
     */
    public function rawAccess(Closure $callback): static
    {
        $callback($this);

        return $this;
    }

    /**
     * Get the dataset query builder.
     *
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function getDatasetQuery(): Builder
    {
        return $this->getModel()->datasetQuery;
    }

    /**
     * Get the query grammar.
     */
    protected function getGrammar(): Grammar
    {
        return $this->getQuery()->getGrammar();
    }

    /**
     * Prepare the query for execution.
     *
     * @return $this
     */
    public function prepare(): static
    {
        if ($this->prepared) {
            return $this;
        }

        $this->prepared = true;

        $grammar = $this->getGrammar();
        $report = $this->getModel();
        $dataset = $this->getDatasetQuery();

        // Get groups and aggregators to use
        $groups = $this->resolveGroups($report);
        $aggregators = $this->resolveAggregators($report);

        // Apply groups to dataset and collect columns
        $datasetColumns = [];
        foreach ($groups as $group) {
            $group->applyToDataset($dataset);
            $datasetColumns = array_merge($datasetColumns, $group->getDatasetColumns());
        }

        // Apply aggregators to dataset and collect columns
        foreach ($aggregators as $aggregator) {
            $aggregator->applyToDataset($dataset);
            $datasetColumns = array_merge($datasetColumns, $aggregator->getDatasetColumns());
        }

        // Add dataset columns to the dataset query
        foreach (array_unique($datasetColumns) as $column) {
            $dataset->addSelect(DB::raw($column));
        }

        // Build the report query with groups
        foreach ($groups as $group) {
            $group->applyJoins($this);
            $this->addSelect($group->toSelectExpression($grammar));
            $this->groupByRaw((string) $group->toGroupByExpression($grammar)->getValue($grammar));
        }

        // Build the report query with aggregators
        foreach ($aggregators as $aggregator) {
            $this->addSelect($aggregator->toExpression($grammar));
        }

        // Build the CTE from the dataset query
        $datasetSql = $dataset->toRawSql();
        $this->withExpression($report->getTable(), $datasetSql);

        return $this;
    }

    /**
     * Resolve which groups to use.
     *
     * @return array<Group>
     */
    protected function resolveGroups(Report $report): array
    {
        $availableGroups = $report->getVisibleGroups();

        if (empty($this->selectedGroups)) {
            return [];
        }

        $groups = [];
        foreach ($this->selectedGroups as $groupName) {
            $group = $report->getGroup($groupName);

            if ($group !== null && $group->isVisible()) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * Resolve which aggregators to use.
     *
     * @return array<Aggregator>
     */
    protected function resolveAggregators(Report $report): array
    {
        $availableAggregators = $report->getVisibleAggregators();

        // If no specific aggregators requested, use all visible ones
        if (empty($this->selectedAggregators)) {
            return $availableAggregators;
        }

        $aggregators = [];
        foreach ($this->selectedAggregators as $aggregatorName) {
            $aggregator = $report->getAggregator($aggregatorName);

            if ($aggregator !== null && $aggregator->isVisible()) {
                $aggregators[] = $aggregator;
            }
        }

        return $aggregators;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array<int, string>|string  $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function get($columns = ['*'])
    {
        $this->prepare();

        /** @var \Illuminate\Database\Eloquent\Collection<int, TModel> */
        return parent::get($columns);
    }

    /**
     * Get the first result.
     *
     * @param  array<string>|string  $columns
     * @return TModel|null
     */
    public function first($columns = ['*'])
    {
        $this->prepare();

        return parent::first($columns);
    }

    /**
     * Paginate the given query.
     *
     * @param  int|null|\Closure  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  \Closure|int|null  $total
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        $this->prepare();

        /** @var int|null $resolvedPerPage */
        $resolvedPerPage = $perPage instanceof \Closure ? $perPage() : $perPage;

        /** @var array<int, string> $resolvedColumns */
        $resolvedColumns = is_string($columns) ? [$columns] : $columns;

        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, TModel> */
        return parent::paginate($resolvedPerPage, $resolvedColumns, $pageName, $page, $total);
    }

    /**
     * Get a lazy collection for the given query.
     *
     * @return \Illuminate\Support\LazyCollection<int, TModel>
     */
    public function cursor()
    {
        $this->prepare();

        return parent::cursor();
    }

    /**
     * Execute a callback over each item.
     *
     * @param  int  $count
     * @return bool
     */
    public function each(callable $callback, $count = 1000)
    {
        $this->prepare();

        return parent::each($callback, $count);
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $this->prepare();

        return parent::chunk($count, $callback);
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql()
    {
        $this->prepare();

        return parent::toSql();
    }

    /**
     * Get the raw SQL representation of the query.
     *
     * @return string
     */
    public function toRawSql()
    {
        $this->prepare();

        return parent::toRawSql();
    }
}
