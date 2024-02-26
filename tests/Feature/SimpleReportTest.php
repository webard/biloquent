<?php

declare(strict_types=1);

use Workbench\App\Models\Channel;
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

test('with relation', function () {
    $channel1 = \Workbench\App\Models\Channel::create(['name' => 'Channel 1']);
    $channel2 = \Workbench\App\Models\Channel::create(['name' => 'Channel 2']);

    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => $channel1->id]);
    Order::create(['no' => '#002', 'value' => 3000, 'created_at' => '2023-12-01', 'channel_id' => $channel2->id]);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2023-01-01', 'channel_id' => $channel1->id]);
    Order::create(['no' => '#004', 'value' => 1000, 'created_at' => '2024-01-01', 'channel_id' => $channel1->id]);

    $report = OrderReport::query()
        ->grouping(['year', 'channel_id'])
        ->summary(['average_value'])
        ->enhance(function ($q) use ($channel1) {
            $q->whereYear('orders.created_at', 2023)->whereRelation('channel', 'id', $channel1->id);
        })
        ->prepare()
        ->with(['channel' => fn ($q) => $q->select('id', 'name')]);

    expect($report->toRawSql())->toBe('with `order_reports` as (select `orders`.`created_at` as `orders_created_at`, `orders`.`channel_id` as `orders_channel_id`, `orders`.`value` as `average_value` from `orders` where year(`orders`.`created_at`) = 2023 and exists (select * from `channels` where `orders`.`channel_id` = `channels`.`id` and `id` = 1)) select YEAR(orders_created_at) as year, orders_channel_id as channel_id, AVG(average_value) as average_value from `order_reports` group by YEAR(orders_created_at), orders_channel_id');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023,
            'channel_id' => 1,
            'average_value' => '1500.00',
            'channel' => [
                'id' => 1,
                'name' => 'Channel 1',
            ]],
    ],
    );
});

test('direct selects field', function () {
    $channel = Channel::create(['name' => 'Channel 1']);
    $channel2 = Channel::create(['name' => 'Channel 2']);

    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01', 'channel_id' => $channel->id]);

    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01', 'channel_id' => $channel->id]);

    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-02', 'channel_id' => $channel2->id]);

    $report = OrderReport::query()
        ->grouping(['year'])
        ->summary(['total_orders', 'average_per_channel'])
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        })
        ->prepare();

    expect($report->toRawSql())->toBe('with `order_reports` as (select `orders`.`created_at` as `orders_created_at`, `orders`.`id` as `total_orders`, `orders`.`id` as `orders_average_per_channel_order_id`, `orders`.`channel_id` as `orders_average_per_channel_channel_id` from `orders` where year(`orders`.`created_at`) = 2023) select YEAR(orders_created_at) as year, COUNT(total_orders) as total_orders, ROUND(COUNT(orders_average_per_channel_order_id)/COUNT(DISTINCT orders_average_per_channel_channel_id),1) as average_per_channel from `order_reports` group by YEAR(orders_created_at)');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_orders' => 3, 'average_per_channel' => '1.5'],
    ]);
});

test('direct builders field', function () {
    $channel = Channel::create(['name' => 'Channel 1']);
    $channel2 = Channel::create(['name' => 'Channel 2']);

    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01', 'channel_id' => $channel->id]);

    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01', 'channel_id' => $channel->id]);

    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-02', 'channel_id' => $channel2->id]);

    $report = OrderReport::query()
        ->grouping(['year'])
        ->summary(['total_orders', 'total_channels'])
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        })
        ->prepare();

    expect($report->toRawSql())->toBe('with `order_reports` as (select `orders`.`created_at` as `orders_created_at`, `orders`.`id` as `total_orders`, `orders`.`channel_id` as `total_channels` from `orders` where year(`orders`.`created_at`) = 2023) select YEAR(orders_created_at) as year, COUNT(total_orders) as total_orders, COUNT(DISTINCT total_channels) as total_channels from `order_reports` group by YEAR(orders_created_at)');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_orders' => 3, 'total_channels' => 2],
    ]);
})->group('lol');
