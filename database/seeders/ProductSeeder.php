<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'kopi',
            'slug' => 'kopi',
            'price' => 10000,
            'is_active' => true,
            'category_id' => 3,
            'stock' => 10,
        ]);

        Product::create([
            'name' => 'susu',
            'slug' => 'susu',
            'price' => 15000,
            'is_active' => true,
            'category_id' => 2,
            'stock' => 10,
        ]);
    }
}
