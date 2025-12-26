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

        // Seed customer groups early so other seeders (pricing/discounts/customers) can rely on them.
        $this->call([
            CustomerGroupSeeder::class,
        ]);

        // Seed Lunar demo data
        // You can choose from several options:
        
        // Option 1: Use LunarDemoSeeder (default) - Creates detailed demo data with specific products
        // $this->call([
        //     LunarDemoSeeder::class,
        // ]);
        
        // Option 5: Use CompleteSeeder with all new factories - Maximum comprehensive seeding
        $this->call([
            CompleteSeeder::class,
            CategorySeeder::class,
            ReviewSeeder::class,
            SearchSeeder::class,
        ]);
        
        // Option 2: Use FactorySeeder - Creates data using factories (more flexible)
        // $this->call([
        //     FactorySeeder::class,
        // ]);
        
        // Option 3: Use CompleteSeeder - Maximum comprehensive seeder with everything
        // $this->call([
        //     CompleteSeeder::class,
        // ]);
        
        // Option 4: Use individual seeders for specific data
        // $this->call([
        //     FactorySeeder::class,
        //     ProductSeeder::class,
        //     CollectionSeeder::class,
        //     CustomerSeeder::class,
        //     CartSeeder::class,
        //     OrderSeeder::class,
        // ]);
    }
}
