<?php

namespace Workbench\App\Reports;

use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Aggregators\Avg;
use Webard\Biloquent\Aggregators\Count;
use Webard\Biloquent\Aggregators\Direct;
use Webard\Biloquent\Aggregators\Sum;
use Webard\Biloquent\Report;
use Workbench\App\Models\Channel;
use Workbench\App\Models\Order;

class OrderReport extends Report
{
    public $casts = [
        'total_orders' => 'integer',
        'total_channels' => 'integer',
        'total_value' => 'decimal:2',
        'average_value' => 'decimal:2',
        'average_per_channel' => 'decimal:1',
    ];

    public function dataset(): EloquentBuilder
    {
        return Order::query();
    }

    public function aggregators(): array
    {
        return [
            'total_orders' => Count::field('total_orders', 'orders.id'),
            'total_channels' => Direct::builders(
                function ($dataset) {
                    return $dataset->addSelect('orders.channel_id as total_channels');
                },
                function ($report) {
                    return $report->addSelect(DB::raw('COUNT(DISTINCT total_channels) as total_channels'));
                }
            ),
            'total_value' => Sum::field('total_value', 'orders.value'),
            'average_value' => Avg::field('average_value', 'orders.value'),

            'average_per_channel' => Direct::selects([
                'orders.id as orders_average_per_channel_order_id',
                'orders.channel_id as orders_average_per_channel_channel_id',
            ],
                DB::raw('ROUND(COUNT(orders_average_per_channel_order_id)/COUNT(DISTINCT orders_average_per_channel_channel_id),1) as average_per_channel')),
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
