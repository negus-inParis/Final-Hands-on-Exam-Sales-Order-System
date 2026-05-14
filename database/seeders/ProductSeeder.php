<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create(['name' => 'Kornbip-19', 'description' => 'Kornbip product', 'cost_price' => 69.00]);
        Product::create(['name' => 'Birch Tree', 'description' => 'Birch Tree product', 'cost_price' => 15.00]);
        Product::create(['name' => 'Red Horse', 'description' => 'Red Horse product', 'cost_price' => 150.00]);
        Product::create(['name' => 'Clvb 2L', 'description' => 'Clvb 2L product', 'cost_price' => 250.00]);
        Product::create(['name' => 'Turon', 'description' => 'Turon product', 'cost_price' => 750.00]);
    }
}
