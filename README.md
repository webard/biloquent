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
        return [
            'total_orders' => Count::field('total_orders', 'id'),
            'average_amount' => Avg::field('average_amount', 'total'),
        ];
    }

    public function groups(): array
    {
        return [
            'day' => ['aggregator' => 'DAY(created_at)'],
            'month' => ['aggregator' => 'MONTH(created_at)'],
            'year' => ['aggregator' => 'YEAR(created_at)'],
            'date' => ['aggregator' => 'DATE(created_at)'],
            'customer_id' => ['field' => 'customer_id', 'aggregator' => 'customer_id'],
        ];
    }
}
```

Now you can use report:

```php
$report = (new OrderReport)->grouping(['month','year'])->aggregate(['total_orders', 'average_amount'])->enhance(function($model) {
    $model->whereYear('created_at','>=', '2023');
});
```
