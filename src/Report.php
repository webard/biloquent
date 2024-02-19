<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
    public array $summaries;

    protected static string $model;

    public BuilderContract $dataset;

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

        $builder->withExpression($this->getTable(), $datasetQuery);

        return $builder;
    }

    /**
     * Method defines the groups for the report.
     * By this data the report will be grouped, like by year, month, etc.
     *
     * @return array<string,mixed>
     */
    abstract public function groups(): array;

    /**
     * Method defines the aggregators for the report.
     * By this data the report will be aggregated, like sum, count, etc.
     *
     * @return array<string,ReportAggregatorField>
     */
    abstract public function aggregators(): array;

    abstract public function dataset(): BuilderContract;
}
