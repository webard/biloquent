<?php

declare(strict_types=1);

namespace Workbench\App\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Aggregators\Avg;
use Webard\Biloquent\Aggregators\Count;
use Webard\Biloquent\Aggregators\Max;
use Webard\Biloquent\Aggregators\Min;
use Webard\Biloquent\Aggregators\Raw;
use Webard\Biloquent\Aggregators\Sum;
use Webard\Biloquent\Groups\ColumnGroup;
use Webard\Biloquent\Groups\DateGroup;
use Webard\Biloquent\Groups\RelationGroup;
use Webard\Biloquent\Report;
use Workbench\App\Models\Channel;
use Workbench\App\Models\Order;

class OrderReport extends Report
{
    protected $table = 'order_report';

    public $casts = [
        'total_orders' => 'integer',
        'total_channels' => 'integer',
        'total_value' => 'decimal:2',
        'average_value' => 'decimal:2',
        'average_per_channel' => 'decimal:1',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'completed_orders' => 'integer',
    ];

    public function dataset(): Builder
    {
        return Order::query();
    }

    public function groups(): array
    {
        return [
            DateGroup::make('day')
                ->column('orders.created_at')
                ->extract('day'),

            DateGroup::make('month')
                ->column('orders.created_at')
                ->extract('month'),

            DateGroup::make('year')
                ->column('orders.created_at')
                ->extract('year'),

            DateGroup::make('date')
                ->column('orders.created_at')
                ->extract('date'),

            ColumnGroup::make('channel_id')
                ->column('orders.channel_id'),

            RelationGroup::make('channel')
                ->relation('channel')
                ->foreignKey('orders.channel_id')
                ->ownerKey('channels.id')
                ->displayColumn('channels.name')
                ->table('channels')
                ->as('channel_name'),
        ];
    }

    public function aggregators(): array
    {
        return [
            Count::make('total_orders')
                ->column('orders.id'),

            Raw::make('total_channels')
                ->datasetColumns(['orders.channel_id as total_channels_col'])
                ->select(DB::raw('COUNT(DISTINCT total_channels_col)')),

            Sum::make('total_value')
                ->column('orders.value'),

            Avg::make('average_value')
                ->column('orders.value'),

            Min::make('min_value')
                ->column('orders.value')
                ->label('Minimum Value'),

            Max::make('max_value')
                ->column('orders.value')
                ->label('Maximum Value'),

            Raw::make('average_per_channel')
                ->datasetColumns([
                    'orders.id as orders_average_per_channel_order_id',
                    'orders.channel_id as orders_average_per_channel_channel_id',
                ])
                ->select(DB::raw('ROUND(COUNT(orders_average_per_channel_order_id) * 1.0 / COUNT(DISTINCT orders_average_per_channel_channel_id), 1)')),

            // Example of conditional aggregator
            Count::make('completed_orders')
                ->column('orders.id')
                ->filter(fn (Builder $q) => $q->where('status', 'completed'))
                ->hidden(), // Hidden by default

            // Example of conditional visibility
            Count::make('visible_orders')
                ->column('orders.id')
                ->when(true),

            Count::make('hidden_orders')
                ->column('orders.id')
                ->when(false),
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
