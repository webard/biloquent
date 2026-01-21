<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use Webard\Biloquent\Contracts\Aggregator;
use Webard\Biloquent\Contracts\Group;

/**
 * Base class for Biloquent reports.
 *
 * Extend this class and implement the abstract methods to define your report.
 *
 * @method static ReportBuilder<static> query()
 */
abstract class Report extends Model
{
    use ForwardsCalls;

    /**
     * The dataset query builder instance.
     *
     * @var Builder<\Illuminate\Database\Eloquent\Model>
     */
    public Builder $datasetQuery;

    /**
     * Forward calls to the dataset model.
     *
     * @param  array<mixed>  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        if (in_array($method, ['hydrate'], true)) {
            return $this->forwardCallTo($this->newQuery(), $method, $parameters);
        }

        return $this->forwardCallTo($this->datasetQuery->getModel(), $method, $parameters);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return ReportBuilder<static>
     */
    public function newEloquentBuilder($query): ReportBuilder
    {
        /** @var ReportBuilder<static> */
        return new ReportBuilder($query);
    }

    /**
     * Create a new Report instance.
     *
     * @param  array<mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->datasetQuery = $this->dataset();
    }

    /**
     * Define the base dataset query for the report.
     *
     * This should return an Eloquent query builder for the model
     * that will be used as the data source for aggregations.
     *
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    abstract public function dataset(): Builder;

    /**
     * Define the available groups for this report.
     *
     * Groups define how the data can be grouped (e.g., by year, month, category).
     *
     * @return array<Group>
     */
    abstract public function groups(): array;

    /**
     * Define the available aggregators for this report.
     *
     * Aggregators define the calculations performed on the data
     * (e.g., count, sum, average).
     *
     * @return array<Aggregator>
     */
    abstract public function aggregators(): array;

    /**
     * Get a group by name.
     */
    public function getGroup(string $name): ?Group
    {
        foreach ($this->groups() as $group) {
            if ($group->getName() === $name) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Get an aggregator by name.
     */
    public function getAggregator(string $name): ?Aggregator
    {
        foreach ($this->aggregators() as $aggregator) {
            if ($aggregator->getName() === $name) {
                return $aggregator;
            }
        }

        return null;
    }

    /**
     * Get visible groups.
     *
     * @return array<Group>
     */
    public function getVisibleGroups(): array
    {
        return array_filter(
            $this->groups(),
            fn (Group $group) => $group->isVisible()
        );
    }

    /**
     * Get visible aggregators.
     *
     * @return array<Aggregator>
     */
    public function getVisibleAggregators(): array
    {
        return array_filter(
            $this->aggregators(),
            fn (Aggregator $aggregator) => $aggregator->isVisible()
        );
    }
}
