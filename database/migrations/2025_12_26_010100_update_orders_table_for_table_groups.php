<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('table_numbers')->nullable()->after('type');
        });

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'table_id') && Schema::hasTable('tables')) {
            DB::table('orders')
                ->whereNotNull('orders.table_id')
                ->leftJoin('tables', 'orders.table_id', '=', 'tables.id')
                ->select('orders.id', 'tables.number')
                ->orderBy('orders.id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        if ($row->number) {
                            DB::table('orders')
                                ->where('id', $row->id)
                                ->update(['table_numbers' => json_encode([(int) $row->number])]);
                        }
                    }
                }, 'orders.id', 'orders_id');
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'table_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['table_id']);
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('table_id');
            });
        }

        if (Schema::hasTable('tables')) {
            Schema::dropIfExists('tables');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->integer('capacity')->default(4);
            $table->enum('status', ['libre', 'ocupada', 'reservada'])->default('libre');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('table_id')->nullable()->after('type');
            $table->foreign('table_id')->references('id')->on('tables')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('table_numbers');
        });
    }
};
