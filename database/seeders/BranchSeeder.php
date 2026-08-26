<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::updateOrCreate(
            ['name' => 'Tulamben Scuba'],
            [
                'brand' => 'tulambenscuba',
                'domain' => 'tulambenscuba.com',
                'is_active' => true,
            ]
        );

        Branch::updateOrCreate(
            ['name' => 'ScubaGo'],
            [
                'brand' => 'scubago',
                'domain' => 'scubago.id',
                'is_active' => true,
            ]
        );
    }
}
