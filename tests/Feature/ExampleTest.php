<?php
use Workbench\App\Models\Order;
use Workbench\App\Reports\OrderReport;

test('model', function() {
    Order::create(['name' => 'test', 'value' => 1000]);
    Order::create(['name' => 'test', 'value' => 2000]);

    var_dump('sdf');

    
    $lol = (new OrderReport)->newQuery()->grouping(['year'])->aggregate(['total_orders']);

    // var_dump($lol->toArray());
})->group('lol');