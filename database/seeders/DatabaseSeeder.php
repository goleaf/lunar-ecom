<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Lunar\Admin\Models\Staff;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default admin staff user for Lunar admin panel
        // Staff users are separate from regular users and used for admin panel access
        $staff = Staff::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Ensure the admin role exists and assign it to the staff user
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'staff']
        );

        if (!$staff->hasRole('admin')) {
            $staff->assignRole('admin');
        }

        // Seed Lunar demo data
        $this->call([
            LunarDemoSeeder::class,
        ]);
    }
}
