<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\LunarTestHelpers;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use LunarTestHelpers;

    /**
    * Ensure database is migrated and seeded with minimal Lunar defaults.
    */
    protected function setUp(): void
    {
        parent::setUp();

        // Force in-memory cache to avoid external Redis during tests
        config([
            'cache.default' => 'array',
            'cache.stores.redis' => ['driver' => 'array'],
            // Disable pricing Redis cache + metrics in tests (these services call Redis directly in prod).
            'pricing_cache.enabled' => false,
            'pricing_cache.store' => 'array',
            'pricing_cache.observability.enabled' => false,
        ]);

        $this->seedLunarTestData();
    }
}
