<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentResourceRecordPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_resource_edit_and_view_routes_do_not_500_when_factory_exists(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $panel = filament()->getPanel('admin');
        $resources = $panel->getResources();

        $this->assertNotEmpty($resources, 'Expected at least one Filament resource to be registered in the admin panel.');

        $tested = 0;

        foreach ($resources as $resource) {
            $slug = $resource::getSlug();
            $modelClass = $resource::getModel();

            if (! is_string($modelClass) || $modelClass === '' || ! class_exists($modelClass)) {
                continue;
            }

            // Only test record pages for resources with factories.
            if (! method_exists($modelClass, 'factory')) {
                continue;
            }

            try {
                $record = $modelClass::factory()->create();
            } catch (\Throwable) {
                // Some models may expose ::factory() but not have a concrete factory.
                continue;
            }

            foreach (['view', 'edit'] as $page) {
                $routeName = "filament.admin.resources.{$slug}.{$page}";

                if (! Route::has($routeName)) {
                    continue;
                }

                $response = $this->get(route($routeName, [
                    'record' => $record,
                ]));

                $this->assertNotEquals(
                    500,
                    $response->getStatusCode(),
                    "Filament resource route [{$routeName}] returned 500 for [{$resource}]."
                );

                $tested++;
            }
        }

        $this->assertGreaterThan(0, $tested, 'Expected at least one Filament resource edit/view route to be tested.');
    }
}

