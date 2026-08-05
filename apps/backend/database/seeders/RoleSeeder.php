<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Administrator yang memiliki akses penuh ke sistem.',
                'is_active' => true,
            ]
        );

        Role::updateOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'User',
                'description' => 'Pengguna umum aplikasi AI Smart Travel Planner.',
                'is_active' => true,
            ]
        );
    }
}