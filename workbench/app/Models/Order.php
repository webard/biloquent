<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['name', 'value'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
