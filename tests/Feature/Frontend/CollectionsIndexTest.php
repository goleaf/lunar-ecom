<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_index_renders(): void
    {
        $response = $this->get('/collections');

        $response->assertOk();
        $response->assertSee('Collections');
    }

    public function test_collections_index_shows_empty_state(): void
    {
        $response = $this->get('/collections');

        $response->assertOk();
        $response->assertSee('No collections found.');
    }
}


