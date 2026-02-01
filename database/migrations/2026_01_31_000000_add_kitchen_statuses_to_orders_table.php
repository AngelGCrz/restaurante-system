<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add kitchen statuses to orders.status enum. Using raw statement for broader DB support.
        // Adjust the enum to include 'en_preparacion' and 'listo'.
        DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM('pendiente','en_preparacion','listo','pagado','cancelado') NOT NULL DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM('pendiente','pagado','cancelado') NOT NULL DEFAULT 'pendiente'");
    }
};
