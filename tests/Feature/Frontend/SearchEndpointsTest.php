<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_autocomplete_returns_empty_when_query_too_short(): void
    {
        $this->getJson(route('frontend.search.autocomplete', ['q' => 'a']))
            ->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_search_autocomplete_returns_data_and_history_keys(): void
    {
        $this->getJson(route('frontend.search.autocomplete', ['q' => 'ab']))
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'history',
            ]);
    }

    public function test_search_popular_endpoint_returns_data_key(): void
    {
        $this->getJson(route('frontend.search.popular'))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_search_trending_endpoint_returns_data_key(): void
    {
        $this->getJson(route('frontend.search.trending'))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }
}

