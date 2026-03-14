<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar enum('mesa','llevar') a string para soportar nuevos tipos
        DB::statement("ALTER TABLE orders MODIFY COLUMN type VARCHAR(30) NOT NULL DEFAULT 'mesa'");
    }

    public function down(): void
    {
        // Limpiar datos no-enum antes de revertir
        DB::table('orders')->whereNotIn('type', ['mesa', 'llevar'])->update(['type' => 'mesa']);
        DB::statement("ALTER TABLE orders MODIFY COLUMN type ENUM('mesa','llevar') NOT NULL DEFAULT 'mesa'");
    }
};
