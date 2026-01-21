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
        ->grouping('year')
        ->summary('total_orders')
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        });

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_orders' => 2],
    ]);
});

test('sum summary', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_value')
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        });

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_value' => '3000.00'],
    ]);
});

test('avg summary', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('average_value')
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        });

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'average_value' => '1500.00'],
    ]);
});

test('with relation', function () {
    $channel1 = Channel::create(['name' => 'Channel 1']);
    $channel2 = Channel::create(['name' => 'Channel 2']);

    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => $channel1->id]);
    Order::create(['no' => '#002', 'value' => 3000, 'created_at' => '2023-12-01', 'channel_id' => $channel2->id]);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2023-01-01', 'channel_id' => $channel1->id]);
    Order::create(['no' => '#004', 'value' => 1000, 'created_at' => '2024-01-01', 'channel_id' => $channel1->id]);

    $report = OrderReport::query()
        ->grouping('year', 'channel_id')
        ->summary('average_value')
        ->enhance(function ($q) use ($channel1) {
            $q->whereYear('orders.created_at', 2023)->whereRelation('channel', 'id', $channel1->id);
        })
        ->with(['channel' => fn ($q) => $q->select('id', 'name')]);

    expect($report->get()->toArray())->toBe([
        [
            'year' => 2023,
            'channel_id' => 1,
            'average_value' => '1500.00',
            'channel' => [
                'id' => 1,
                'name' => 'Channel 1',
            ],
        ],
    ]);
});

test('raw aggregator with selects', function () {
    $channel = Channel::create(['name' => 'Channel 1']);
    $channel2 = Channel::create(['name' => 'Channel 2']);

    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#004', 'value' => 1000, 'created_at' => '2023-12-02', 'channel_id' => $channel2->id]);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders', 'average_per_channel')
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        });

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_orders' => 3, 'average_per_channel' => '1.5'],
    ]);
});

test('raw aggregator with distinct count', function () {
    $channel = Channel::create(['name' => 'Channel 1']);
    $channel2 = Channel::create(['name' => 'Channel 2']);

    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#003', 'value' => 1000, 'created_at' => '2024-01-01', 'channel_id' => $channel->id]);
    Order::create(['no' => '#004', 'value' => 1000, 'created_at' => '2023-12-02', 'channel_id' => $channel2->id]);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders', 'total_channels')
        ->enhance(function ($q) {
            $q->whereYear('orders.created_at', 2023);
        });

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'total_orders' => 3, 'total_channels' => 2],
    ]);
});

test('multiple groups', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-11-20']);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->grouping('year', 'month')
        ->summary('total_orders', 'total_value');

    $results = $report->get()->toArray();

    expect($results)->toHaveCount(2);
    expect($results[0])->toMatchArray(['year' => 2023, 'month' => 11, 'total_orders' => 2]);
    expect($results[1])->toMatchArray(['year' => 2023, 'month' => 12, 'total_orders' => 1]);
});

test('no grouping returns all aggregators', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->summary('total_orders', 'total_value', 'average_value');

    $result = $report->first();

    expect($result->total_orders)->toBe(2);
    expect($result->total_value)->toBe('3000.00');
    expect($result->average_value)->toBe('1500.00');
});

test('date group extracts date correctly', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-15 10:30:00']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-11-15 14:45:00']);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-11-16 09:00:00']);

    $report = OrderReport::query()
        ->grouping('date')
        ->summary('total_orders');

    $results = $report->get();

    expect($results)->toHaveCount(2);
});
