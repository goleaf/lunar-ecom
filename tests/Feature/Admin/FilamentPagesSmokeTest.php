<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_pages_routes_are_registered_and_do_not_500(): void
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

            // Only test Filament panel "pages" routes (non-resource).
            if (! str_starts_with($name, 'filament.admin.pages.')) {
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

            $this->assertNotEquals(
                500,
                $response->status(),
                "Filament page route [{$name}] returned 500."
            );

            $tested++;
        }

        $this->assertGreaterThan(0, $tested, 'Expected at least one Filament page route to be registered.');
    }
}

