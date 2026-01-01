<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrontendLivewirePagesDoNotDelegateToControllersTest extends TestCase
{
    public function test_frontend_livewire_pages_do_not_reference_http_controllers(): void
    {
        $directory = app_path('Livewire/Frontend/Pages');

        $this->assertTrue(is_dir($directory), "Expected directory [{$directory}] to exist.");

        $violations = [];

        foreach (File::allFiles($directory) as $file) {
            $contents = $file->getContents();

            if (str_contains($contents, 'App\\Http\\Controllers\\')) {
                $violations[] = $file->getRelativePathname();
            }

            if (str_contains($contents, 'Controller::class')) {
                $violations[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], array_values(array_unique($violations)));
    }
}

