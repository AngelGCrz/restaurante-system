<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Categorías base para el flujo del restaurante
        DB::table('categories')->insert([
            ['name' => 'Entrada', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Menu', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Extra', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bebidas', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
