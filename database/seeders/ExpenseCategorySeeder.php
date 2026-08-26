<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (ExpenseCategory::SLUGS as $slug => $name) {
            ExpenseCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }
    }
}
