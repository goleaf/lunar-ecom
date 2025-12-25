<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Example migration for creating custom permissions and roles.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/access-control
 * 
 * Lunar uses Spatie Laravel Permission package for role and permission management.
 * Permissions should be created via migrations (not from the admin panel) so they
 * can be deployed to other environments easily.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create custom permissions
        Permission::firstOrCreate(['name' => 'custom-action']);
        Permission::firstOrCreate(['name' => 'view-custom-page']);
        Permission::firstOrCreate(['name' => 'manage-custom-resource']);

        // Optionally create a custom role and assign permissions
        $customRole = Role::firstOrCreate(['name' => 'custom-role']);
        $customRole->givePermissionTo([
            'custom-action',
            'view-custom-page',
        ]);

        // You can also assign permissions to existing roles (e.g., 'admin', 'staff')
        // $adminRole = Role::findByName('admin');
        // $adminRole->givePermissionTo('custom-action');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        Permission::whereIn('name', [
            'custom-action',
            'view-custom-page',
            'manage-custom-resource',
        ])->delete();

        // Remove custom role (if created)
        Role::where('name', 'custom-role')->delete();
    }
};


