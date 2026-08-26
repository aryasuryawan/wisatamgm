<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Wisata', 'type_slug' => 'wisata', 'sort_order' => 1],
            ['name' => 'Jasa', 'type_slug' => 'jasa', 'sort_order' => 2],
            ['name' => 'Makanan & Minuman', 'type_slug' => 'makanan', 'sort_order' => 3],
            ['name' => 'Sewa Alat', 'type_slug' => 'sewa-alat', 'sort_order' => 4],
            ['name' => 'Transportasi', 'type_slug' => 'transportasi', 'sort_order' => 5],
            ['name' => 'Merchandise', 'type_slug' => 'merchandise', 'sort_order' => 6],
        ];

        foreach ($categories as $cat) {
            ProductCategory::updateOrCreate(
                ['type_slug' => $cat['type_slug']],
                $cat
            );
        }
    }
}
