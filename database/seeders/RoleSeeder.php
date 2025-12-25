<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Role::create(['name' => 'admin', 'display_name' => 'Administrador']);
        \App\Models\Role::create(['name' => 'cajero', 'display_name' => 'Cajero']);
        \App\Models\Role::create(['name' => 'cocina', 'display_name' => 'Cocina']);
        \App\Models\Role::create(['name' => 'mozo', 'display_name' => 'Mozo']);
    }
}
