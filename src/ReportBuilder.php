<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\ForwardsCalls;
use Webard\Biloquent\Contracts\ReportAggregatorField;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Webard\Biloquent\Report>
 */
class ReportBuilder extends Builder
{
    use ForwardsCalls;

    /**
     * @var array<int,mixed>
     */
    private array $grouping = [];

    /**
     * @var array<int,mixed>
     */
    private array $summaries = [];

    public function __construct($query)
    {
        parent::__construct($query);
    }

    public function grouping(array $grouping): self
    {
        $this->grouping = $grouping;

        return $this;
    }

    public function summary(array $summaries): self
    {
        $this->summaries = $summaries;

        return $this;
    }

    /**
     * @deprecated Use summary() instead
     */
    public function columns(array $columns): self
    {
        $this->summary($columns);

        return $this;
    }

    public function enhance(callable $enhancer): self
    {
        $enhancer($this->getModel()->dataset);

        return $this;
    }

    /**
     * Prepare the query for execution.
     *
     * @return Builder<Report>
     */
    public function prepare()
    {
        $builder = $this;

        $availableGroups = $this->getModel()->groups();

        // Apply groupings to the query and select the dataset
        foreach ($this->grouping as $group) {
            if (isset($availableGroups[$group])) {

                $builder->addSelect(DB::raw($availableGroups[$group]['aggregator'].' as '.$group));
                if (isset($availableGroups[$group]['field'])) {
                    $this->getModel()->dataset->addSelect($availableGroups[$group]['field']);
                }
                $builder->groupByRaw($availableGroups[$group]['aggregator']);
            }
        }

        $aggregators = $this->getModel()->aggregators();

        // Apply aggregators to the query
        foreach ($this->summaries as $summary) {
            if (isset($aggregators[$summary])) {

                $aggregatorClass = $aggregators[$summary];

                assert($aggregatorClass instanceof ReportAggregatorField);
                assert($this->getModel()->dataset instanceof Builder);

                $aggregatorClass->applyToBuilder($builder, $this->getModel()->dataset);

            }
        }

        // Prepare the dataset query
        $datasetQuery = $this->getModel()->dataset->toRawSql();

        // Apply the dataset query to the main query as a Common Table Expression
        $builder->withExpression($this->getModel()->getTable(), $datasetQuery);

        return $builder;
    }
}
