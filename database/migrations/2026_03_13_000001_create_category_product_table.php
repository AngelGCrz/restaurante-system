<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla pivot con precio por categoría
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->unique(['category_id', 'product_id']);
            $table->timestamps();
        });

        // 2. Migrar datos existentes: category_id + price → pivot
        DB::table('products')
            ->whereNotNull('category_id')
            ->select('id', 'category_id', 'price')
            ->orderBy('id')
            ->each(function ($product) {
                DB::table('category_product')->insertOrIgnore([
                    'category_id' => $product->category_id,
                    'product_id'  => $product->id,
                    'price'       => $product->price,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            });

        // 3. Eliminar category_id de products (price se mantiene como base/fallback)
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }

    public function down(): void
    {
        // Restaurar category_id en products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
        });

        // Restaurar category_id desde pivot (primera categoría)
        DB::table('category_product')->orderBy('id')->get()->each(function ($row) {
            DB::table('products')
                ->where('id', $row->product_id)
                ->whereNull('category_id')
                ->update([
                    'category_id' => $row->category_id,
                    'price'       => $row->price,
                ]);
        });

        Schema::dropIfExists('category_product');
    }
};
