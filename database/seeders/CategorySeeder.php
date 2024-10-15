<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat category
        Category::create([
            'name' => 'Tea Series',
            'slug' => 'tea-series',
            'is_active' => true,
        ]);
        Category::create([
            'name' => 'Milky Series',
            'slug' => 'milky-series',
            'is_active' => true,
        ]);
        Category::create([
            'name' => 'Coffee Series',
            'slug' => 'coffee-series',
            'is_active' => true,
        ]);
    }
}
