<?php

declare(strict_types=1);

namespace Webard\Biloquent\Aggregators;

use Webard\Biloquent\ReportColumnField;
use Webard\Biloquent\ReportRelationField;

class Kurtosis
{
    /**
     * @return ReportColumnField
     */
    public static function field(string $alias, string $column)
    {
        return new ReportColumnField($alias, $column, 'kurtosis');
    }

    /**
     * @return ReportRelationField
     */
    public static function relation(string $alias, string $relation, string $column)
    {
        return new ReportRelationField($alias, $relation, $column, 'kurtosis', 'kurtosis');
    }
}
