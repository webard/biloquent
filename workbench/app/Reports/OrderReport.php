<?php

namespace Workbench\App\Reports;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webard\Biloquent\Report;
use Workbench\App\Models\Order;
use Webard\Biloquent\Aggregators\Count;
use Webard\Biloquent\Aggregators\Sum;
use Workbench\App\Models\Channel;

class OrderReport extends Report
{
    public static string $model = Order::class;

      /**
     * @var array<int,mixed>
     */
    public array $grouping = ['year'];

    /**
     * @var array<int,mixed>
     */
    public array $columns = ['total_orders', 'total_value'];

    public function aggregators(): array
    {
        return [
            'total_orders' => Count::field('total_orders', 'id'),
            'total_value' => Sum::field('total_value', 'value'),
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function groups(): array
    {
        return [
            'day' => ['aggregator' => 'DAY(created_at)'],
            'month' => ['aggregator' => 'MONTH(created_at)'],
            'year' => ['aggregator' => 'YEAR(created_at)'],
            'date' => ['aggregator' => 'DATE(created_at)'],
        ];
    }
}
