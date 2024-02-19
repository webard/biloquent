<?php

namespace Workbench\App\Reports;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webard\Biloquent\Aggregators\Count;
use Webard\Biloquent\Aggregators\Sum;
use Webard\Biloquent\Report;
use Workbench\App\Models\Channel;
use Workbench\App\Models\Order;

class OrderReport extends Report
{
    public static string $model = Order::class;

    public $casts = [
        'total_orders' => 'integer',
        'total_value' => 'decimal:2',
    ];

    public function aggregators(): array
    {
        return [
            'total_orders' => Count::field('total_orders', 'orders.id'),
            'total_value' => Sum::field('total_value', 'orders.value'),
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function groups(): array
    {
        return [
            'day' => ['aggregator' => 'DAY(orders_created_at)', 'field' => 'orders.created_at as orders_created_at'],
            'month' => ['aggregator' => 'MONTH(orders_created_at)', 'field' => 'orders.created_at as orders_created_at'],
            'year' => ['aggregator' => 'YEAR(orders_created_at)', 'field' => 'orders.created_at as orders_created_at'],
            'date' => ['aggregator' => 'DATE(orders_created_at)', 'field' => 'orders.created_at as orders_created_at'],
        ];
    }
}
