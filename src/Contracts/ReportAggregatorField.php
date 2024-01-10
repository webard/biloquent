<?php

declare(strict_types=1);

namespace Webard\Biloquent\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface ReportAggregatorField
{
    public function applyToBuilder(Builder &$report, Builder &$dataset): self;
}
