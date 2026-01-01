<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class SearchAnalyticsEndpointsTest extends TestCase
{
    public function test_search_analytics_statistics_endpoint_returns_data(): void
    {
        $this->getJson(route('frontend.search-analytics.statistics', ['period' => 'week']))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_searches',
                    'searches_with_results',
                    'zero_result_searches',
                    'searches_with_clicks',
                ],
            ]);
    }

    public function test_search_analytics_zero_results_endpoint_returns_data(): void
    {
        $this->getJson(route('frontend.search-analytics.zero-results', ['period' => 'week', 'limit' => 10]))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_search_analytics_trends_endpoint_returns_data(): void
    {
        $this->getJson(route('frontend.search-analytics.trends', ['period' => 'week', 'interval' => 'day']))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_search_analytics_most_clicked_endpoint_returns_data(): void
    {
        $this->getJson(route('frontend.search-analytics.most-clicked', ['period' => 'week', 'limit' => 10]))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }
}

