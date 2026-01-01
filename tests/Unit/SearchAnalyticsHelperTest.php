<?php

namespace Tests\Unit;

use App\Lunar\Search\SearchAnalyticsHelper;
use App\Models\Product;
use App\Models\SearchAnalytic;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SearchAnalyticsHelperTest extends TestCase
{
    public function test_get_statistics_counts_zero_results_and_clicks_independently(): void
    {
        Cache::flush();

        $clickedProduct = Product::factory()->create();

        SearchAnalytic::create([
            'search_term' => 'jacket',
            'result_count' => 5,
            'zero_results' => false,
            'clicked_product_id' => $clickedProduct->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'user_id' => null,
            'filters' => [],
            'session_id' => 'sess-1',
            'searched_at' => now(),
        ]);

        SearchAnalytic::create([
            'search_term' => 'madeupterm',
            'result_count' => 0,
            'zero_results' => true,
            'clicked_product_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'user_id' => null,
            'filters' => [],
            'session_id' => 'sess-2',
            'searched_at' => now(),
        ]);

        Cache::flush();

        $stats = SearchAnalyticsHelper::getStatistics('week');

        $this->assertSame(2, $stats['total_searches']);
        $this->assertSame(1, $stats['zero_result_searches']);
        $this->assertSame(1, $stats['searches_with_results']);
        $this->assertSame(1, $stats['searches_with_clicks']);

        $this->assertSame(50.0, (float) $stats['zero_result_rate']);
        $this->assertSame(50.0, (float) $stats['click_through_rate']);
        $this->assertSame(2.5, (float) $stats['average_results_per_search']);
    }
}

