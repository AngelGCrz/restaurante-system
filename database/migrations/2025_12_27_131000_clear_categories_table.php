<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getSchemaBuilder()->hasTable('categories')) {
            Schema::disableForeignKeyConstraints();
            DB::table('products')->update(['category_id' => null]);
            DB::table('categories')->delete();
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // No-op: table remains empty
    }
};
