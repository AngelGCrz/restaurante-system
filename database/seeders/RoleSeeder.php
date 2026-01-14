<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrador']
        );

        Role::updateOrCreate(
            ['name' => 'cajero'],
            ['display_name' => 'Cajero']
        );

        Role::updateOrCreate(
            ['name' => 'cocina'],
            ['display_name' => 'Cocina']
        );

        Role::updateOrCreate(
            ['name' => 'mozo'],
            ['display_name' => 'Mozo']
        );

        Role::UpdateOrCreate(
            ['name' => 'adm'],
            ['display_name' => 'adm']
        );
    }
}
