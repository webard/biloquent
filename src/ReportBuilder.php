<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Contracts\ReportAggregatorField;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Webard\Biloquent\Report>
 */
class ReportBuilder extends Builder
{
    public function __construct($query)
    {
        parent::__construct($query);
    }

    public function grouping(array $grouping): self
    {
        $this->getModel()->grouping = $grouping;

        return $this;
    }

    public function summary(array $summaries): self
    {
        $this->getModel()->summaries = $summaries;

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
        $builder = parent::applyScopes();

        $availableGroups = $this->getModel()->groups();

        foreach ($this->getModel()->grouping as $group) {
            if (isset($availableGroups[$group])) {

                $builder->addSelect(DB::raw($availableGroups[$group]['aggregator'].' as '.$group));
                if (isset($availableGroups[$group]['field'])) {
                    $this->getModel()->dataset->addSelect($availableGroups[$group]['field']);
                }
                $builder->groupByRaw($availableGroups[$group]['aggregator']);
            }
        }

        $aggregators = $this->getModel()->aggregators();

        foreach ($this->getModel()->summaries as $summary) {
            if (isset($aggregators[$summary])) {

                $aggregatorClass = $aggregators[$summary];

                assert($aggregatorClass instanceof ReportAggregatorField);
                assert($this->getModel()->dataset instanceof Builder);

                $aggregatorClass->applyToBuilder($builder, $this->getModel()->dataset);

            }
        }

        $datasetQuery = $this->getModel()->dataset->toRawSql();

        $builder->withExpression($this->getModel()->getTable(), $datasetQuery);

        return $builder;
    }
}
