<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::insert([
            ['name' => 'Kemeja Formal', 'price' => 150000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kaos Polos Cotton Combed', 'price' => 75000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jaket Hoodie', 'price' => 250000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tas Tote Bag Kanvas', 'price' => 45000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Topi Baseball', 'price' => 50000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
