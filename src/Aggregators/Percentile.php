<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Contracts\ReportAggregatorField;

class Percentile
{
    /**
     * @return ReportAggregatorField
     */
    public static function field(string $alias, string $column, float $percentile)
    {
        return
        new class($alias, $column, $percentile) implements ReportAggregatorField
        {
            public function __construct(
                public string $alias,
                public string $column,
                public float $percentile,
            ) {
            }

            /**
             * @return string
             */
            private function prepareAggregateExpression()
            {
                return 'percentile_cont('.$this->alias.','.($this->percentile).')';
            }

            public function applyToBuilder(Builder &$report, Builder &$dataset): self
            {
                $dataset->addSelect($this->column.' as '.$this->alias);
                $report->addSelect(DB::raw($this->prepareAggregateExpression().' as '.$this->alias));

                return $this;
            }
        };
    }

    /**
     * @return ReportAggregatorField
     */
    public static function relation(string $alias, string $relation, string $column, float $percentile)
    {
        return
        new class($alias, $relation, $column, $percentile) implements ReportAggregatorField
        {
            public function __construct(
                public string $alias,
                public string $relation,
                public string $column,
                public float $percentile,
            ) {
            }

            public function applyToBuilder(Builder &$report, Builder &$dataset): self
            {
                $dataset->withAggregate($this->relation.' as '.$this->alias, 'total', 'count');

                $report->addSelect(DB::raw('percentile_cont('.$this->alias.', '.($this->percentile).') as '.$this->alias));

                return $this;
            }
        };
    }
}
