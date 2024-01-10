<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Contracts\ReportFieldResolver;

class ReportRelationField implements ReportFieldResolver
{
    public function __construct(
        public string $alias,
        public string $relation,
        public string $column,
        public string $aggregator,
        public string $reportAggregator,
    ) {
    }

    public function applyToBuilder(Report &$report, Builder &$dataset): self
    {
        $dataset->withAggregate($this->relation, $this->column, $this->aggregator);

        $report->addSelect(DB::raw($this->reportAggregator.'('.$this->relation.'_'.$this->aggregator.'_'.$this->column.') as '.$this->alias));

        return $this;
    }
}
