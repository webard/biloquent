# Biloquent

Eloquent-based report builder with fluent API and multi-driver support for Laravel.

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12%2B-red)](https://laravel.com/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

## Features

- **Fluent API** - Filament-inspired `::make()` factory pattern
- **Multi-driver support** - MySQL, MariaDB, PostgreSQL, SQLite
- **Date grouping** - Automatic driver-aware date extraction (year, month, day, date)
- **Relation groups** - Group by related model attributes with automatic joins
- **Statistical aggregators** - Median, Percentile, Skewness, Kurtosis (driver-specific)
- **CTE-based queries** - Uses Common Table Expressions for optimal performance
- **Conditional aggregators** - Show/hide aggregators dynamically with `->when()`

## Requirements

- PHP 8.3+
- Laravel 12+
- Database: MySQL 8+, MariaDB 10.6+, PostgreSQL 15+, or SQLite 3.45+

## Installation

```bash
composer require webard/biloquent
```

## Database-Specific Features

### Statistical Functions

| Aggregator | MySQL | MariaDB | PostgreSQL | SQLite |
|------------|-------|---------|------------|--------|
| Count, Sum, Avg, Min, Max | ✅ | ✅ | ✅ | ✅ |
| Median | ✅ (UDF) | ❌ | ✅ | ✅* |
| Percentile | ✅ (UDF) | ❌ | ✅ | ✅* |
| Skewness | ✅ (UDF) | ✅ (UDF) | ❌ | ❌ |
| Kurtosis | ✅ (UDF) | ✅ (UDF) | ❌ | ❌ |

- **UDF** = Requires [UDF Infusion](https://github.com/infusion/udf_infusion) extension
- ***** = Requires SQLite compiled with `SQLITE_ENABLE_PERCENTILE` flag

## Quick Start

### 1. Create a Report Class

```php
<?php

namespace App\Reports;

use Illuminate\Database\Eloquent\Builder;
use Webard\Biloquent\Aggregators\{Avg, Count, Sum};
use Webard\Biloquent\Groups\{ColumnGroup, DateGroup, RelationGroup};
use Webard\Biloquent\Report;
use App\Models\Order;

class OrderReport extends Report
{
    protected $table = 'order_report';

    public $casts = [
        'total_orders' => 'integer',
        'total_value' => 'decimal:2',
        'average_value' => 'decimal:2',
    ];

    public function dataset(): Builder
    {
        return Order::query();
    }

    public function groups(): array
    {
        return [
            DateGroup::make('year')
                ->column('orders.created_at')
                ->extract('year'),

            DateGroup::make('month')
                ->column('orders.created_at')
                ->extract('month'),

            DateGroup::make('date')
                ->column('orders.created_at')
                ->extract('date'),

            ColumnGroup::make('status')
                ->column('orders.status'),

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

            Sum::make('total_value')
                ->column('orders.value')
                ->label('Total Revenue'),

            Avg::make('average_value')
                ->column('orders.value'),
        ];
    }
}
```

### 2. Use the Report

```php
use App\Reports\OrderReport;

// Basic usage with grouping
$report = OrderReport::query()
    ->grouping('year', 'month')
    ->summary('total_orders', 'total_value')
    ->get();

// With conditions on the dataset
$report = OrderReport::query()
    ->grouping('month')
    ->summary('total_orders', 'total_value', 'average_value')
    ->enhance(function ($dataset) {
        $dataset->where('status', 'completed')
            ->whereYear('created_at', 2024);
    })
    ->get();

// Global summary (no grouping)
$totals = OrderReport::query()
    ->summary('total_orders', 'total_value')
    ->enhance(fn ($q) => $q->whereYear('created_at', 2024))
    ->first();

// With pagination
$paginated = OrderReport::query()
    ->grouping('date')
    ->summary('total_orders')
    ->orderByDesc('date')
    ->paginate(25);
```

### Example Output

```php
[
    ['year' => 2024, 'month' => 1, 'total_orders' => 150, 'total_value' => '45000.00'],
    ['year' => 2024, 'month' => 2, 'total_orders' => 180, 'total_value' => '52000.00'],
    ['year' => 2024, 'month' => 3, 'total_orders' => 200, 'total_value' => '61000.00'],
]
```

## Available Components

### Groups

#### DateGroup

Extract date parts with automatic driver-aware SQL:

```php
DateGroup::make('year')->column('orders.created_at')->extract('year');
DateGroup::make('month')->column('orders.created_at')->extract('month');
DateGroup::make('day')->column('orders.created_at')->extract('day');
DateGroup::make('date')->column('orders.created_at')->extract('date');

// Fluent shortcuts
DateGroup::make('year')->column('orders.created_at')->year();
DateGroup::make('month')->column('orders.created_at')->month();
```

#### ColumnGroup

Group by a simple column value:

```php
ColumnGroup::make('status')
    ->column('orders.status')
    ->as('order_status'); // Optional: custom alias
```

#### RelationGroup

Group by related model with automatic join:

```php
RelationGroup::make('channel')
    ->relation('channel')
    ->foreignKey('orders.channel_id')
    ->ownerKey('channels.id')
    ->displayColumn('channels.name')
    ->table('channels')
    ->joinType('left') // 'left' (default) or 'inner'
    ->as('channel_name');
```

### Aggregators

#### Basic Aggregators

```php
Count::make('total_orders')->column('orders.id');
Count::make('unique_customers')->column('orders.customer_id')->distinct();
Sum::make('total_value')->column('orders.value');
Avg::make('average_value')->column('orders.value');
Min::make('min_value')->column('orders.value');
Max::make('max_value')->column('orders.value');
```

#### Statistical Aggregators

```php
// Median (MySQL UDF, PostgreSQL native, SQLite with flag)
Median::make('median_value')->column('orders.value');

// Percentile (MySQL UDF, PostgreSQL native, SQLite with flag)
Percentile::make('p90_value')
    ->column('orders.value')
    ->percentile(0.9);

// Skewness & Kurtosis (MySQL/MariaDB UDF only)
Skewness::make('value_skewness')->column('orders.value');
Kurtosis::make('value_kurtosis')->column('orders.value');
```

#### Raw Aggregator (Escape Hatch)

For custom SQL expressions:

```php
Raw::make('custom_metric')
    ->datasetColumns(['orders.qty as qty', 'orders.price as price'])
    ->select(DB::raw('SUM(qty * price) / COUNT(*)'));
```

### Conditional Aggregators

Show/hide aggregators dynamically:

```php
Median::make('median_value')
    ->column('orders.value')
    ->when($this->showAdvancedStats);

Count::make('vip_orders')
    ->column('orders.id')
    ->filter(fn ($q) => $q->where('customer.vip', true))
    ->hidden(); // Hidden by default
```

## Advanced Usage

### Custom Dataset Modifications

```php
OrderReport::query()
    ->grouping('month')
    ->enhance(function ($dataset) {
        $dataset->whereHas('customer', fn ($q) => $q->where('vip', true))
            ->where('status', '!=', 'cancelled');
    })
    ->get();
```

### Raw Query Access

```php
OrderReport::query()
    ->rawAccess(function ($builder) {
        // Access the underlying query builder
        $builder->getDatasetQuery()
            ->join('customers', 'orders.customer_id', '=', 'customers.id');
    })
    ->grouping('month')
    ->get();
```

### Loading Relations

```php
OrderReport::query()
    ->grouping('channel_id')
    ->summary('total_orders')
    ->with(['channel' => fn ($q) => $q->select('id', 'name')])
    ->get();
```

## How It Works

Biloquent uses Common Table Expressions (CTEs) to build reports:

1. **Dataset Query**: Your base model query becomes a CTE
2. **Group Application**: Groups are applied with driver-specific SQL
3. **Aggregation**: Aggregators compute values over the grouped data

```sql
WITH order_report AS (
    SELECT orders.created_at AS orders_created_at,
           orders.id AS orders_id,
           orders.value AS orders_value
    FROM orders
    WHERE status = 'completed'
)
SELECT YEAR(orders_created_at) AS year,
       COUNT(orders_id) AS total_orders,
       SUM(orders_value) AS total_value
FROM order_report
GROUP BY YEAR(orders_created_at)
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.