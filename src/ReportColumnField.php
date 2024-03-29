<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Contracts\ReportAggregatorField;

class ReportColumnField implements ReportAggregatorField
{
    public function __construct(
        public string $alias,
        public string $column,
        public string $aggregator,
    ) {
    }

    private function prepareAggregateExpression(): string
    {
        return mb_strtoupper($this->aggregator).'('.$this->alias.')';
    }

    public function applyToBuilder(Builder &$report, Builder &$dataset): self
    {
        $dataset->addSelect($this->column.' as '.$this->alias);
        $report->addSelect(DB::raw($this->prepareAggregateExpression().' as '.$this->alias));

        return $this;
    }
}
