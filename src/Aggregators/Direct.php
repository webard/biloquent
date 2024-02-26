<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Expression;
use Webard\Biloquent\Contracts\ReportAggregatorField;

class Direct
{
    public static function builders(Closure $datasetSelect, Closure $builderSelect): ReportAggregatorField
    {
        return new class($datasetSelect, $builderSelect) implements ReportAggregatorField
        {
            public function __construct(
                public Closure $datasetSelect,
                public Closure $builderSelect
            ) {
            }

            public function applyToBuilder(EloquentBuilder &$report, EloquentBuilder &$dataset): self
            {

                $dataset = ($this->datasetSelect)($dataset);

                $report = ($this->builderSelect)($report);

                return $this;
            }
        };
    }

    public static function selects(string|array|Expression $datasetSelect, string|array|Expression $builderSelect): ReportAggregatorField
    {
        return new class($datasetSelect, $builderSelect) implements ReportAggregatorField
        {
            public function __construct(
                public string|array|Expression $datasetSelect,
                public string|array|Expression $builderSelect
            ) {
            }

            public function applyToBuilder(Builder &$report, Builder &$dataset): self
            {
                if (is_array($this->datasetSelect)) {
                    foreach ($this->datasetSelect as $select) {
                        $dataset->addSelect($select);
                    }
                } else {
                    $dataset->addSelect($this->datasetSelect);
                }

                if (is_array($this->builderSelect)) {
                    foreach ($this->builderSelect as $select) {
                        $report->addSelect($select);
                    }

                } else {
                    $report->addSelect($this->builderSelect);
                }

                return $this;
            }
        };
    }
}
