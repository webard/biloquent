<?php

declare(strict_types=1);

namespace Webard\Biloquent;

use Illuminate\Database\Eloquent\Builder;

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
}
