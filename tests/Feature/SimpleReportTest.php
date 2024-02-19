<?php

declare(strict_types=1);

use Workbench\App\Models\Order;
use Workbench\App\Reports\OrderReport;

test('report without relations', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping(['year'])
        ->summary(['total_orders', 'total_value'])
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        })
        ->prepare();

    expect($report->toRawSql())->toBe('with `p` as (select `orders`.`created_at` as `orders_created_at`, `orders`.`id` as `total_orders`, `orders`.`value` as `total_value` from `orders` where year(`orders`.`created_at`) = 2023) select YEAR(orders_created_at) as year, COUNT(total_orders) as total_orders, SUM(total_value) as total_value from `p` group by YEAR(orders_created_at)');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_orders' => 1, 'total_value' => '2000.00'],
    ]);
});
