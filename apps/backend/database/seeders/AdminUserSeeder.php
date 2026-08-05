<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            [
                'email' => 'admin@travelplanner.local',
            ],
            [
                'name' => 'Admin Travel Planner',
                'password' => Hash::make('Admin123!'),
                'email_verified_at' => now(),
            ]
        );

        $adminRole = Role::query()
            ->where('slug', 'admin')
            ->firstOrFail();

        $admin->roles()->syncWithoutDetaching([
            $adminRole->id,
        ]);

        $user = User::updateOrCreate(
            [
                'email' => 'user@travelplanner.local',
            ],
            [
                'name' => 'Demo User',
                'password' => Hash::make('User123!'),
                'email_verified_at' => now(),
            ]
        );

        $userRole = Role::query()
            ->where('slug', 'user')
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([
            $userRole->id,
        ]);
    }
}
