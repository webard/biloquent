<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use Webard\Biloquent\Contracts\ReportAggregatorField;

abstract class Report extends Model
{
    use ForwardsCalls;

    /**
     * @deprecated
     */
    public static string $model;

    public BuilderContract $dataset;

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

    /**
     * Method defines the dataset for the report.
     * TODO: This method should be abstract in v2, and "public static string $model" should be removed.
     */
    public function dataset(): BuilderContract
    {
        // @phpstan-ignore-next-line
        $model = static::$model;

        return $model::query();

    }
}
