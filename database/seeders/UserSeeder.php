<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        \App\Models\User::create([
            'name' => 'Admin Restaurante',
            'email' => 'admin@restaurante.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        \App\Models\User::create([
            'name' => 'Cajero 1',
            'email' => 'caja@restaurante.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => $cajeroRole->id,
        ]);

        \App\Models\User::create([
            'name' => 'Cocina 1',
            'email' => 'cocina@restaurante.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => $cocinaRole->id,
        ]);

        \App\Models\User::create([
            'name' => 'Mozo 1',
            'email' => 'mozo@restaurante.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => $mozoRole->id,
        ]);
    }
}
