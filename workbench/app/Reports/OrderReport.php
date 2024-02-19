<?php

namespace Workbench\App\Reports;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webard\Biloquent\Aggregators\Avg;
use Webard\Biloquent\Aggregators\Count;
use Webard\Biloquent\Aggregators\Sum;
use Webard\Biloquent\Report;
use Workbench\App\Models\Channel;
use Workbench\App\Models\Order;

class OrderReport extends Report
{
    public $casts = [
        'total_orders' => 'integer',
        'total_value' => 'decimal:2',
        'average_value' => 'decimal:2',
    ];

    public function dataset(): Builder
    {
        return Order::query();
    }

    public function aggregators(): array
    {
        return [
            'total_orders' => Count::field('total_orders', 'orders.id'),
            'total_value' => Sum::field('total_value', 'orders.value'),
            'average_value' => Avg::field('average_value', 'orders.value'),
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
            'channel_id' => ['field' => 'orders.channel_id as orders_channel_id', 'aggregator' => 'orders_channel_id'],
        ];
    }
}
