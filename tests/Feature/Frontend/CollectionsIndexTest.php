<?php

namespace Tests\Feature\Frontend;

use App\Models\Collection;
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

    public function test_collection_filter_page_redirects_to_livewire_collection_show(): void
    {
        $collection = Collection::factory()->create();

        $this->get(route('frontend.collections.filter', ['collection' => $collection->getKey()]))
            ->assertRedirect(route('frontend.collections.show', ['slug' => (string) $collection->getKey()]));
    }
}




