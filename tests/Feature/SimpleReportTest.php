<?php

declare(strict_types=1);

use Workbench\App\Models\Channel;
use Workbench\App\Models\Order;
use Workbench\App\Reports\OrderReport;

// ==========================================
// Basic Aggregators Tests
// ==========================================

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

// ==========================================
// Min/Max Aggregators Tests
// ==========================================

test('min aggregator', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 500, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('min_value');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'min_value' => '500.00'],
    ]);
});

test('max aggregator', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 500, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('max_value');

    expect($report->get()->toArray())->toBe([
        ['year' => 2023, 'max_value' => '2000.00'],
    ]);
});

test('min and max together', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 500, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('min_value', 'max_value');

    $result = $report->first();

    expect($result->min_value)->toBe('500.00');
    expect($result->max_value)->toBe('2000.00');
});

// ==========================================
// DateGroup Tests
// ==========================================

test('date group extracts day correctly', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-11-20']);

    $report = OrderReport::query()
        ->grouping('day')
        ->summary('total_orders');

    $results = $report->get();

    expect($results)->toHaveCount(2);
    expect($results[0]->day)->toBe(15);
    expect($results[0]->total_orders)->toBe(2);
    expect($results[1]->day)->toBe(20);
    expect($results[1]->total_orders)->toBe(1);
});

test('date group extracts month correctly', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-11-20']);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->grouping('month')
        ->summary('total_orders');

    $results = $report->get();

    expect($results)->toHaveCount(2);
    expect($results[0]->month)->toBe(11);
    expect($results[0]->total_orders)->toBe(2);
    expect($results[1]->month)->toBe(12);
    expect($results[1]->total_orders)->toBe(1);
});

test('date group extracts year correctly', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-15']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders');

    $results = $report->get();

    expect($results)->toHaveCount(2);
    expect($results[0]->year)->toBe(2023);
    expect($results[1]->year)->toBe(2024);
});

// ==========================================
// RelationGroup Tests
// ==========================================

test('relation group with display column', function () {
    $channel1 = Channel::create(['name' => 'Online']);
    $channel2 = Channel::create(['name' => 'Retail']);

    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => $channel1->id]);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-11-02', 'channel_id' => $channel1->id]);
    Order::create(['no' => '#003', 'value' => 1500, 'created_at' => '2023-11-03', 'channel_id' => $channel2->id]);

    $report = OrderReport::query()
        ->grouping('channel')
        ->summary('total_orders', 'total_value');

    $results = $report->get();

    expect($results)->toHaveCount(2);
    expect($results[0]->channel_name)->toBe('Online');
    expect($results[0]->total_orders)->toBe(2);
    expect($results[1]->channel_name)->toBe('Retail');
    expect($results[1]->total_orders)->toBe(1);
});

// ==========================================
// Visibility/Conditions Tests
// ==========================================

test('hidden aggregator is not included', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);

    $report = OrderReport::query()
        ->summary('total_orders', 'hidden_orders');

    $result = $report->first();

    expect($result->total_orders)->toBe(1);
    expect(isset($result->hidden_orders))->toBeFalse();
});

test('visible aggregator is included', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);

    $report = OrderReport::query()
        ->summary('visible_orders');

    $result = $report->first();

    expect($result->visible_orders)->toBe(1);
});

test('when condition controls visibility', function () {
    $report = new OrderReport();

    $visibleAggregators = $report->getVisibleAggregators();
    $names = array_map(fn ($a) => $a->getName(), $visibleAggregators);

    expect($names)->toContain('visible_orders');
    expect($names)->not->toContain('hidden_orders');
    expect($names)->not->toContain('completed_orders');
});

// ==========================================
// Label/Alias Tests
// ==========================================

test('aggregator label is set correctly', function () {
    $report = new OrderReport();

    $minValue = $report->getAggregator('min_value');
    $maxValue = $report->getAggregator('max_value');

    expect($minValue->getLabel())->toBe('Minimum Value');
    expect($maxValue->getLabel())->toBe('Maximum Value');
});

test('group alias works correctly', function () {
    $report = new OrderReport();

    $channelGroup = $report->getGroup('channel');

    expect($channelGroup->getAlias())->toBe('channel_name');
});

// ==========================================
// Pagination Tests
// ==========================================

test('paginate works correctly', function () {
    for ($i = 1; $i <= 25; $i++) {
        Order::create(['no' => '#'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'value' => $i * 100, 'created_at' => "2023-{$i}-01"]);
    }

    $report = OrderReport::query()
        ->grouping('month')
        ->summary('total_orders');

    $paginated = $report->paginate(10);

    expect($paginated->count())->toBe(10);
    expect($paginated->total())->toBe(12); // 12 months
    expect($paginated->lastPage())->toBe(2);
});

test('paginate with custom page', function () {
    for ($i = 1; $i <= 12; $i++) {
        Order::create(['no' => '#'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'value' => $i * 100, 'created_at' => "2023-{$i}-01"]);
    }

    $report = OrderReport::query()
        ->grouping('month')
        ->summary('total_orders');

    $page2 = $report->paginate(5, ['*'], 'page', 2);

    expect($page2->count())->toBe(5);
    expect($page2->currentPage())->toBe(2);
});

// ==========================================
// Cursor/Lazy Tests
// ==========================================

test('cursor returns lazy collection', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->grouping('month')
        ->summary('total_orders');

    $cursor = $report->cursor();

    expect($cursor)->toBeInstanceOf(\Illuminate\Support\LazyCollection::class);
    expect($cursor->count())->toBe(2);
});

// ==========================================
// Chunk/Each Tests
// ==========================================

// Note: chunk() and each() require ORDER BY on a column that exists in the CTE.
// These methods use orderBy('id') by default which doesn't exist in aggregated reports.
// Users should use cursor() or get() instead, or implement custom chunking logic.

test('chunk with explicit ordering', function () {
    for ($i = 1; $i <= 12; $i++) {
        Order::create(['no' => '#'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'value' => $i * 100, 'created_at' => "2023-{$i}-01"]);
    }

    $report = OrderReport::query()
        ->grouping('month')
        ->summary('total_orders')
        ->rawAccess(fn ($q) => $q->reorder('month'));

    $chunks = [];
    $report->chunk(5, function ($records) use (&$chunks) {
        $chunks[] = $records->count();
    });

    expect($chunks)->toBe([5, 5, 2]);
});

test('each with explicit ordering', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);

    $report = OrderReport::query()
        ->grouping('month')
        ->summary('total_orders')
        ->rawAccess(fn ($q) => $q->reorder('month'));

    $count = 0;
    $report->each(function ($record) use (&$count) {
        $count++;
    });

    expect($count)->toBe(2);
});

// ==========================================
// SQL Output Tests
// ==========================================

test('toSql returns SQL string', function () {
    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders');

    $sql = $report->toSql();

    expect($sql)->toBeString();
    expect($sql)->toContain('order_report');
});

test('toRawSql returns complete SQL with bindings', function () {
    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders');

    $sql = $report->toRawSql();

    expect($sql)->toBeString();
    expect($sql)->toContain('order_report');
});

// ==========================================
// rawAccess Tests
// ==========================================

test('rawAccess allows direct query modification', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);
    Order::create(['no' => '#002', 'value' => 1000, 'created_at' => '2023-12-01']);
    Order::create(['no' => '#003', 'value' => 500, 'created_at' => '2024-01-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders')
        ->rawAccess(function ($query) {
            $query->having('total_orders', '>', 1);
        });

    $results = $report->get();

    expect($results)->toHaveCount(1);
    expect($results[0]->year)->toBe(2023);
    expect($results[0]->total_orders)->toBe(2);
});

// ==========================================
// Report Model Methods Tests
// ==========================================

test('getGroup returns correct group', function () {
    $report = new OrderReport();

    $yearGroup = $report->getGroup('year');
    $monthGroup = $report->getGroup('month');
    $nonExistent = $report->getGroup('nonexistent');

    expect($yearGroup)->not->toBeNull();
    expect($yearGroup->getName())->toBe('year');
    expect($monthGroup)->not->toBeNull();
    expect($nonExistent)->toBeNull();
});

test('getAggregator returns correct aggregator', function () {
    $report = new OrderReport();

    $totalOrders = $report->getAggregator('total_orders');
    $totalValue = $report->getAggregator('total_value');
    $nonExistent = $report->getAggregator('nonexistent');

    expect($totalOrders)->not->toBeNull();
    expect($totalOrders->getName())->toBe('total_orders');
    expect($totalValue)->not->toBeNull();
    expect($nonExistent)->toBeNull();
});

test('getVisibleGroups returns only visible groups', function () {
    $report = new OrderReport();

    $visibleGroups = $report->getVisibleGroups();

    expect($visibleGroups)->not->toBeEmpty();
    foreach ($visibleGroups as $group) {
        expect($group->isVisible())->toBeTrue();
    }
});

test('getVisibleAggregators returns only visible aggregators', function () {
    $report = new OrderReport();

    $visibleAggregators = $report->getVisibleAggregators();

    expect($visibleAggregators)->not->toBeEmpty();
    foreach ($visibleAggregators as $aggregator) {
        expect($aggregator->isVisible())->toBeTrue();
    }
});

// ==========================================
// Edge Cases Tests
// ==========================================

test('empty result set', function () {
    // No orders created
    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders');

    $results = $report->get();

    expect($results)->toHaveCount(0);
});

test('null channel_id is handled correctly', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01', 'channel_id' => null]);

    $report = OrderReport::query()
        ->grouping('channel_id')
        ->summary('total_orders');

    $results = $report->get();

    expect($results)->toHaveCount(1);
    expect($results[0]->channel_id)->toBeNull();
    expect($results[0]->total_orders)->toBe(1);
});

test('multiple summary calls override previous', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-01']);

    $report = OrderReport::query()
        ->grouping('year')
        ->summary('total_orders')
        ->summary('total_value'); // This should override

    $result = $report->first();

    expect(isset($result->total_orders))->toBeFalse();
    expect($result->total_value)->toBe('2000.00');
});

test('multiple grouping calls override previous', function () {
    Order::create(['no' => '#001', 'value' => 2000, 'created_at' => '2023-11-15']);

    $report = OrderReport::query()
        ->grouping('year')
        ->grouping('month') // This should override
        ->summary('total_orders');

    $result = $report->first();

    expect(isset($result->year))->toBeFalse();
    expect($result->month)->toBe(11);
});
