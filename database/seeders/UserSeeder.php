<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $cajeroRole = \App\Models\Role::where('name', 'cajero')->first();
        $cocinaRole = \App\Models\Role::where('name', 'cocina')->first();
        $mozoRole = \App\Models\Role::where('name', 'mozo')->first();

        $now = now();

        \App\Models\User::updateOrCreate(
            ['email' => 'admin@restaurante.com'],
            [
                'name' => 'Admin Restaurante',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'email_verified_at' => $now,
            ],
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'caja@restaurante.com'],
            [
                'name' => 'Cajero 1',
                'password' => Hash::make('password'),
                'role_id' => $cajeroRole->id,
                'email_verified_at' => $now,
            ],
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'cocina@restaurante.com'],
            [
                'name' => 'Cocina 1',
                'password' => Hash::make('password'),
                'role_id' => $cocinaRole->id,
                'email_verified_at' => $now,
            ],
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'mozo@restaurante.com'],
            [
                'name' => 'Mozo 1',
                'password' => Hash::make('password'),
                'role_id' => $mozoRole->id,
                'email_verified_at' => $now,
            ],
        );
    }
}
