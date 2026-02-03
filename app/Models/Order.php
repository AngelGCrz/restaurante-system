<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'customer_name', 'comment', 'type', 'table_numbers', 'total', 'status', 'prepared_at', 'preparation_seconds'];

    protected $casts = [
        'table_numbers' => 'array',
        'prepared_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getTableLabelAttribute(): string
    {
        if (! is_array($this->table_numbers) || empty($this->table_numbers)) {
            return ucfirst($this->type);
        }

        $tables = array_values(array_map('intval', $this->table_numbers));
        $prefix = count($tables) === 1 ? 'Mesa' : 'Mesas';

        return $prefix . ' ' . implode(' + ', $tables);
    }
    public function orderItems()
{
    return $this->hasMany(\App\Models\OrderItem::class);
}

}
