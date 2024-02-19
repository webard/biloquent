<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['no', 'value', 'created_at', 'channel_id'];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
