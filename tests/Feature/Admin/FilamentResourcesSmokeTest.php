<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentResourcesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_resource_index_routes_are_registered_and_do_not_500(): void
    {
        $this->withoutExceptionHandling();

        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $panel = filament()->getPanel('admin');
        $resources = $panel->getResources();

        $this->assertNotEmpty($resources, 'Expected at least one Filament resource to be registered in the admin panel.');

        foreach ($resources as $resource) {
            $slug = $resource::getSlug();
            $indexRoute = "filament.admin.resources.{$slug}.index";

            $this->assertTrue(
                Route::has($indexRoute),
                "Missing route [{$indexRoute}] for Filament resource [{$resource}] (slug: {$slug})."
            );

            $response = $this->get(route($indexRoute));

            $this->assertNotEquals(
                500,
                $response->status(),
                "Filament resource index route [{$indexRoute}] returned 500 for [{$resource}]."
            );

            $createRoute = "filament.admin.resources.{$slug}.create";

            if (!Route::has($createRoute)) {
                continue;
            }

            $createResponse = $this->get(route($createRoute));

            $this->assertNotEquals(
                500,
                $createResponse->status(),
                "Filament resource create route [{$createRoute}] returned 500 for [{$resource}]."
            );
        }
    }
}

