<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::updateOrCreate(
            ['email' => 'owner@tulambenscuba.test'],
            [
                'name' => 'Owner',
                'phone' => null,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $owner->assignRole('owner');
    }
}
