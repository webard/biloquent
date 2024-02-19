<?php

declare(strict_types=1);

use Workbench\App\Models\Order;
use Workbench\App\Reports\OrderReport;

test('count summary', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping(['year'])
        ->summary(['total_orders'])
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        })
        ->prepare();

    expect($report->toRawSql())->toBe('with `order_reports` as (select `orders`.`created_at` as `orders_created_at`, `orders`.`id` as `total_orders` from `orders` where year(`orders`.`created_at`) = 2023) select YEAR(orders_created_at) as year, COUNT(total_orders) as total_orders from `order_reports` group by YEAR(orders_created_at)');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_orders' => 2],
    ]);
});

test('sum summary', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping(['year'])
        ->summary(['total_value'])
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        })
        ->prepare();

    expect($report->toRawSql())->toBe('with `order_reports` as (select `orders`.`created_at` as `orders_created_at`, `orders`.`value` as `total_value` from `orders` where year(`orders`.`created_at`) = 2023) select YEAR(orders_created_at) as year, SUM(total_value) as total_value from `order_reports` group by YEAR(orders_created_at)');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_value' => '3000.00'],
    ]);
});

test('avg summary', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping(['year'])
        ->summary(['average_value'])
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        })
        ->prepare();

    expect($report->toRawSql())->toBe('with `order_reports` as (select `orders`.`created_at` as `orders_created_at`, `orders`.`value` as `average_value` from `orders` where year(`orders`.`created_at`) = 2023) select YEAR(orders_created_at) as year, AVG(average_value) as average_value from `order_reports` group by YEAR(orders_created_at)');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'average_value' => '1500.00'],
    ]);
});
