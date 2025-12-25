<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Salchipapa Clásica', 'price' => 15.00, 'description' => 'Papas fritas con salchicha'],
            ['name' => 'Pollo Broaster', 'price' => 18.00, 'description' => 'Pollo crujiente con papas'],
            ['name' => 'Arroz Chaufa', 'price' => 12.00, 'description' => 'Arroz frito al estilo oriental'],
            ['name' => 'Hamburguesa Simple', 'price' => 10.00, 'description' => 'Hamburguesa con lechuga y tomate'],
            ['name' => 'Caldo de Gallina', 'price' => 14.00, 'description' => 'Caldo reconfortante de gallina'],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
