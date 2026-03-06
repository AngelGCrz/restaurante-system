<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'comment',
        'type',
        'table_numbers',
        'total',
        'status',
        'cancel_reason',
        'payment_method',
        'receipt_type',
        'prepared_at',
        'preparation_seconds',
        'origin_order_id',
    ];

    protected $casts = [
        'table_numbers' => 'array',
        'prepared_at'   => 'datetime',
    ];

    protected $appends = ['table_label'];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Items de este pedido (relación canónica). */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function originOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'origin_order_id');
    }

    public function childOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'origin_order_id');
    }

    public function kitchenBatches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KitchenBatch::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getTableLabelAttribute(): string
    {
        if (! is_array($this->table_numbers) || empty($this->table_numbers)) {
            return ucfirst($this->type);
        }

        $tables = array_values(array_map('intval', $this->table_numbers));
        $prefix = count($tables) === 1 ? 'Mesa' : 'Mesas';

        return $prefix.' '.implode(' + ', $tables);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /** Filtra solo pedidos pendientes. */
    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    /** Filtra pedidos de hoy. */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
