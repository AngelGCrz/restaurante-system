<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Product extends Model
{
    protected $fillable = ['name', 'description', 'price', 'is_available', 'stock'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'stock' => 'integer',
    ];

    /**
     * Check if there is enough stock for a requested quantity.
     */
    public function hasStockFor(int $quantity, bool $allowNegative = false): bool
    {
        if ($allowNegative) {
            return true;
        }

        return $this->stock >= $quantity;
    }

    /**
     * Decrease product stock by quantity (will not check permission here).
     */
    public function decreaseStock(int $quantity, bool $allowNegative = false): void
    {
        $new = $this->stock - $quantity;
        $this->stock = $allowNegative ? $new : max(0, $new);
        $this->save();
    }

    /** Un producto pertenece a múltiples categorías con precio propio por categoría. */
    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot('price')
            ->withTimestamps();
    }

    // 🔍 Scope: búsqueda por nombre
    public function scopeSearch($query, $term)
    {
        if ($term) {
            $query->where('name', 'like', '%' . $term . '%');
        }
    }

    // 🗂 Scope: filtro por categoría (vía pivot)
    public function scopeCategoryFilter($query, $categoryId)
    {
        if ($categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
        }
    }
}
