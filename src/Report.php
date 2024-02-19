<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\ForwardsCalls;
use Webard\Biloquent\Contracts\ReportAggregatorField;

abstract class Report extends Model
{
    /**
     * @var array<int,mixed>
     */
    public array $grouping;

    /**
     * @var array<int,mixed>
     */
    public array $columns;

    protected static string $model;

    /**
     * @var Builder<Report>
     */
    public Builder $dataset;

    use ForwardsCalls;

    /**
     * @param  array<mixed>  $parameters
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['hydrate'], true)) {
            return $this->forwardCallTo($this->newQuery(), $method, $parameters);
        }

        return $this->forwardCallTo($this->dataset->getModel(), $method, $parameters);
    }

    /**
     * @return Builder<Report>
     */
    public function newEloquentBuilder($query)
    {
        return new ReportBuilder($query);
    }

    /**
     * @param  array<mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = 'p';

        $this->dataset = $this->dataset();
    }

    // TODO: try to overwrite query() or get() method and there prepare temporary table
    /**
     * @param  Builder<Report>  $builder
     * @return Builder<Report>
     */
    public function scopePrepare(Builder $builder): Builder
    {

        $availableGroups = $this->groups();

        foreach ($this->grouping as $group) {
            if (isset($availableGroups[$group])) {

                $builder->addSelect(DB::raw($availableGroups[$group]['aggregator'].' as '.$group));
                if (isset($availableGroups[$group]['field'])) {
                    $this->dataset->addSelect($availableGroups[$group]['field']);
                }
                $builder->groupByRaw($availableGroups[$group]['aggregator']);
            }
        }

        $aggregators = $this->aggregators();

        foreach ($this->summaries as $summary) {
            if (isset($aggregators[$summary])) {

                $aggregatorClass = $aggregators[$summary];

                assert($aggregatorClass instanceof ReportAggregatorField);
                assert($this->dataset instanceof Builder);

                $aggregatorClass->applyToBuilder($builder, $this->dataset);

            }
        }

        $datasetQuery = $this->dataset->toRawSql();

        $builder->withExpression('p', $datasetQuery);

        return $builder;
    }

    /**
     * @return array<string,mixed>
     */
    abstract public function groups(): array;

    /**
     * @return array<string,ReportAggregatorField>
     */
    abstract public function aggregators(): array;

    /**
     * @return Builder<Report>
     */
    protected function dataset(): Builder
    {
        $model = static::$model;

        return $model::query();
    }
}
