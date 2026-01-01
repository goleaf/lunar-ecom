<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class AdminRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_named_get_routes_without_parameters_do_not_500(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $tested = 0;

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! is_string($name) || $name === '') {
                continue;
            }

            if (! str_starts_with($name, 'admin.')) {
                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            // Skip routes that require parameters.
            if (! empty($route->parameterNames())) {
                continue;
            }

            $response = $this->get(route($name));
            $status = $response->getStatusCode();

            $this->assertNotEquals(
                500,
                $status,
                "Admin route [{$name}] returned 500."
            );

            $tested++;
        }

        $this->assertGreaterThan(0, $tested, 'Expected at least one admin GET route without parameters to be registered.');
    }
}

