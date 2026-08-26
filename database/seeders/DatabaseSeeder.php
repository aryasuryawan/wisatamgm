<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionSeeder::class,
            BranchSeeder::class,
            ProductCategorySeeder::class,
            ExpenseCategorySeeder::class,
            AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
