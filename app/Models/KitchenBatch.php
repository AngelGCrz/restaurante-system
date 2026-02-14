<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenBatch extends Model
{
    protected $table = 'kitchen_batches';

    protected $fillable = ['order_id', 'user_id', 'items', 'status'];

    protected $casts = [
        'items' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
