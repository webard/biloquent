# BILOQUENT

Reports for Eloquent models.

**Package is under development and in very early stage.**


## UDF Infusion

Some of aggregator needs [UDF Infusion](https://github.com/infusion/udf_infusion) extensions for MySQL:

- kurtosis
- median
- percentile
- skewness

## Sample report

Sample base on Order model with total and created_at fields required.

Create file `App\Reports\OrderReport.php`:

```php
declare(strict_types=1);

namespace App\Reports;

use Webard\Biloquent\Aggregators\Avg;
use Webard\Biloquent\Aggregators\Count;
use Webard\Biloquent\Report;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Order;

class OrderReport extends Report
{
    public static string $model = Order::class;
   
    // Defaults if not defined
    public array $grouping = ['year'];
    public array $columns = ['total_orders'];

    // Define needed relations, they are not taken from source model (yet)
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Define aggregators
    public function aggregators(): array
    {
         // It is good practice to alias column names, but not necessary
        return [
            'total_orders' => Count::field('total_orders', 'orders.id'),
            'average_amount' => Avg::field('average_amount', 'orders.value'),
        ];
    }

    public function groups(): array
    {
        // It is good practice to alias column names, but not necessary
        return [
            'day' => [
                // aggregator will be applied to report query
                'aggregator' => 'DAY(orders_created_at)',
                // field will be fetched from dataset query
                'field' => 'orders.created_at as orders_created_at'
            ],
            'month' => [
                'aggregator' => 'MONTH(orders_created_at)',
                'field' => 'orders.created_at as orders_created_at'
            ],
            'year' => [
                'aggregator' => 'YEAR(orders_created_at)',
                'field' => 'orders.created_at as orders_created_at'
            ],
            'date' => [
                'aggregator' => 'DATE(orders_created_at)',
                'field' => 'orders.created_at as orders_created_at'
            ],
            'channel_id' => [
                'field' => 'channels.channel_id as channels_channel_id',
                'aggregator' => 'channels_channel_id'
            ],
        ];
    }
}
```

Now you can use report:

```php
$report = OrderReport::query()
    // set grouping fields
    ->grouping(['month', 'year'])
    // set which data you want to calculate
    ->summary(['total_orders'])
    // narrow down dataset that you want to calculate
    ->enhance(function($model) {
        $model->whereYear('created_at','>=', '2023');
    })
    // prepare report (this needs to be called before running query)
    ->prepare()
    // get results
    ->get();
```

Example output:
```php
[
    0 =>  [
        'year' => 2023,
        'month' => 12,
        'total_orders' => 5,
    ],
    1 =>  [
        'year' => 2024,
        'month' => 4,
        'total_orders' => 2,
    ],
]
```