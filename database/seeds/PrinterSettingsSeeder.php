<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrinterSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Printer configuration for SAT Q22
        Setting::updateOrCreate(
            ['key' => 'printer_kitchen_mode'],
            ['value' => 'network'] // 'network' for SAT Q22, 'file' for fallback
        );

        Setting::updateOrCreate(
            ['key' => 'printer_kitchen_network_host'],
            ['value' => '192.168.1.100'] // SAT Q22 IP address - update as needed
        );

        Setting::updateOrCreate(
            ['key' => 'printer_kitchen_network_port'],
            ['value' => '9100'] // Standard ESC/POS port
        );
    }
}
