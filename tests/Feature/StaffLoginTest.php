<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Lunar\Admin\Models\Staff;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that staff user is created by DatabaseSeeder
     */
    public function test_staff_user_is_created_by_seeder(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->first();

        $this->assertNotNull($staff);
        $this->assertEquals('Admin', $staff->first_name);
        $this->assertEquals('User', $staff->last_name);
        $this->assertTrue($staff->admin);
        $this->assertTrue(Hash::check('password', $staff->password));
    }

    /**
     * Test that staff user has admin role assigned
     */
    public function test_staff_user_has_admin_role(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->first();

        $this->assertNotNull($staff);
        $this->assertTrue($staff->hasRole('admin'));
    }

    /**
     * Test that staff user can authenticate using Auth facade
     */
    public function test_staff_user_can_authenticate(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->first();

        // Test authentication using the staff guard
        $authenticated = auth('staff')->attempt([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertTrue($authenticated);
        $this->assertEquals($staff->id, auth('staff')->id());
    }

    /**
     * Test that staff user cannot authenticate with wrong password
     */
    public function test_staff_user_cannot_authenticate_with_wrong_password(): void
    {
        $this->seed();

        $authenticated = auth('staff')->attempt([
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertFalse($authenticated);
    }

    /**
     * Test that staff user cannot authenticate with wrong email
     */
    public function test_staff_user_cannot_authenticate_with_wrong_email(): void
    {
        $this->seed();

        $authenticated = auth('staff')->attempt([
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $this->assertFalse($authenticated);
    }

    /**
     * Test that admin role is created if it doesn't exist
     */
    public function test_admin_role_is_created_if_not_exists(): void
    {
        // Ensure no admin role exists
        Role::where('name', 'admin')->where('guard_name', 'staff')->delete();

        $this->seed();

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'staff')->first();

        $this->assertNotNull($adminRole);
        $this->assertEquals('admin', $adminRole->name);
        $this->assertEquals('staff', $adminRole->guard_name);
    }

    /**
     * Test that staff user can access admin panel after login
     */
    public function test_staff_user_can_access_admin_panel_after_login(): void
    {
        $this->withoutExceptionHandling();

        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->first();

        // Login as staff
        $this->actingAs($staff, 'staff');

        // Try to access admin panel
        $response = $this->get('/admin');

        // Should be able to access (status 200 or redirect to dashboard)
        $this->assertContains($response->status(), [200, 302]);
    }

    /**
     * Test that staff user is created with correct attributes
     */
    public function test_staff_user_has_correct_attributes(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->first();

        $this->assertNotNull($staff);
        $this->assertEquals('Admin User', $staff->full_name);
        $this->assertNotNull($staff->email_verified_at);
        $this->assertTrue($staff->admin);
    }
}

