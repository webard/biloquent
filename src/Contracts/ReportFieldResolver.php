<?php

declare(strict_types=1);

namespace Webard\Biloquent\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Webard\Biloquent\Report;

interface ReportFieldResolver
{
    public function applyToBuilder(Report &$report, Builder &$dataset): self;
}
