<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentResourceRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_resource_index_and_create_routes_do_not_500(): void
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

            // Only Filament resource routes.
            if (! str_starts_with($name, 'filament.admin.resources.')) {
                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            // Skip routes that require parameters (e.g. edit/view).
            if (! empty($route->parameterNames())) {
                continue;
            }

            // Focus on the two most important entry points.
            if (! (str_ends_with($name, '.index') || str_ends_with($name, '.create'))) {
                continue;
            }

            $response = $this->get(route($name));

            $this->assertNotEquals(
                500,
                $response->status(),
                "Filament resource route [{$name}] returned 500."
            );

            $tested++;
        }

        $this->assertGreaterThan(0, $tested, 'Expected at least one Filament resource index/create route to be registered.');
    }
}

