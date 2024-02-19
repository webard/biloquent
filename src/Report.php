<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
