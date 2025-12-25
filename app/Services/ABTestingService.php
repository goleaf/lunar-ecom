<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductABTest;
use App\Models\ABTestEvent;
use Illuminate\Support\Facades\Auth;

/**
 * Service for managing A/B tests.
 */
class ABTestingService
{
    /**
     * Create A/B test.
     *
     * @param  Product  $product
     * @param  array  $config
     * @return ProductABTest
     */
    public function createTest(Product $product, array $config): ProductABTest
    {
        return ProductABTest::create([
            'name' => $config['name'],
            'description' => $config['description'] ?? null,
            'product_id' => $product->id,
            'variant_a_id' => $config['variant_a_id'] ?? null,
            'variant_b_id' => $config['variant_b_id'] ?? null,
            'test_type' => $config['test_type'],
            'variant_a_config' => $config['variant_a_config'] ?? null,
            'variant_b_config' => $config['variant_b_config'] ?? null,
            'traffic_split_a' => $config['traffic_split_a'] ?? 50,
            'traffic_split_b' => $config['traffic_split_b'] ?? 50,
            'status' => 'draft',
            'scheduled_start_at' => $config['scheduled_start_at'] ?? null,
            'scheduled_end_at' => $config['scheduled_end_at'] ?? null,
            'min_sample_size' => $config['min_sample_size'] ?? 1000,
            'min_duration_days' => $config['min_duration_days'] ?? 7,
        ]);
    }

    /**
     * Start A/B test.
     *
     * @param  ProductABTest  $test
     * @return ProductABTest
     */
    public function startTest(ProductABTest $test): ProductABTest
    {
        $test->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
        
        return $test->fresh();
    }

    /**
     * Get variant for user/session.
     *
     * @param  ProductABTest  $test
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @return string  'a' or 'b'
     */
    public function getVariant(ProductABTest $test, ?int $userId = null, ?string $sessionId = null): string
    {
        // Check if user/session already has a variant assigned
        $existingEvent = ABTestEvent::where('ab_test_id', $test->id)
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } elseif ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
            })
            ->first();
        
        if ($existingEvent) {
            return $existingEvent->variant;
        }
        
        // Assign variant based on traffic split
        $random = rand(1, 100);
        $variant = $random <= $test->traffic_split_a ? 'a' : 'b';
        
        // Record view event
        $this->recordEvent($test, $variant, 'view', $userId, $sessionId);
        
        return $variant;
    }

    /**
     * Record A/B test event.
     *
     * @param  ProductABTest  $test
     * @param  string  $variant
     * @param  string  $eventType
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @param  array|null  $eventData
     * @return ABTestEvent
     */
    public function recordEvent(
        ProductABTest $test,
        string $variant,
        string $eventType,
        ?int $userId = null,
        ?string $sessionId = null,
        ?array $eventData = null
    ): ABTestEvent {
        return ABTestEvent::create([
            'ab_test_id' => $test->id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'variant' => $variant,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Update test results.
     *
     * @param  ProductABTest  $test
     * @return ProductABTest
     */
    public function updateResults(ProductABTest $test): ProductABTest
    {
        // Get visitors
        $visitorsA = ABTestEvent::where('ab_test_id', $test->id)
            ->where('variant', 'a')
            ->where('event_type', 'view')
            ->distinct('session_id')
            ->count('session_id');
        
        $visitorsB = ABTestEvent::where('ab_test_id', $test->id)
            ->where('variant', 'b')
            ->where('event_type', 'view')
            ->distinct('session_id')
            ->count('session_id');
        
        // Get conversions
        $conversionsA = ABTestEvent::where('ab_test_id', $test->id)
            ->where('variant', 'a')
            ->where('event_type', 'purchase')
            ->count();
        
        $conversionsB = ABTestEvent::where('ab_test_id', $test->id)
            ->where('variant', 'b')
            ->where('event_type', 'purchase')
            ->count();
        
        // Calculate conversion rates
        $conversionRateA = $visitorsA > 0 ? ($conversionsA / $visitorsA) : 0;
        $conversionRateB = $visitorsB > 0 ? ($conversionsB / $visitorsB) : 0;
        
        // Get revenue
        $revenueA = ABTestEvent::where('ab_test_id', $test->id)
            ->where('variant', 'a')
            ->where('event_type', 'purchase')
            ->sum(DB::raw("CAST(JSON_EXTRACT(event_data, '$.revenue') AS DECIMAL(12,2))"));
        
        $revenueB = ABTestEvent::where('ab_test_id', $test->id)
            ->where('variant', 'b')
            ->where('event_type', 'purchase')
            ->sum(DB::raw("CAST(JSON_EXTRACT(event_data, '$.revenue') AS DECIMAL(12,2))"));
        
        // Calculate statistical significance
        $confidenceLevel = $this->calculateConfidenceLevel($visitorsA, $conversionsA, $visitorsB, $conversionsB);
        
        // Determine winner
        $winner = $this->determineWinner($conversionRateA, $conversionRateB, $confidenceLevel);
        
        $test->update([
            'visitors_a' => $visitorsA,
            'visitors_b' => $visitorsB,
            'conversions_a' => $conversionsA,
            'conversions_b' => $conversionsB,
            'conversion_rate_a' => $conversionRateA,
            'conversion_rate_b' => $conversionRateB,
            'revenue_a' => $revenueA,
            'revenue_b' => $revenueB,
            'confidence_level' => $confidenceLevel,
            'winner' => $winner,
        ]);
        
        return $test->fresh();
    }

    /**
     * Calculate confidence level (simplified).
     *
     * @param  int  $visitorsA
     * @param  int  $conversionsA
     * @param  int  $visitorsB
     * @param  int  $conversionsB
     * @return float|null
     */
    protected function calculateConfidenceLevel(
        int $visitorsA,
        int $conversionsA,
        int $visitorsB,
        int $conversionsB
    ): ?float {
        // Simplified confidence calculation
        // In production, use proper statistical tests (chi-square, z-test, etc.)
        if ($visitorsA < 30 || $visitorsB < 30) {
            return null; // Not enough data
        }
        
        $rateA = $visitorsA > 0 ? ($conversionsA / $visitorsA) : 0;
        $rateB = $visitorsB > 0 ? ($conversionsB / $visitorsB) : 0;
        
        // Simplified: if difference is > 5%, assume 95% confidence
        $difference = abs($rateA - $rateB);
        return $difference > 0.05 ? 95.0 : null;
    }

    /**
     * Determine winner.
     *
     * @param  float  $rateA
     * @param  float  $rateB
     * @param  float|null  $confidenceLevel
     * @return string|null
     */
    protected function determineWinner(float $rateA, float $rateB, ?float $confidenceLevel): ?string
    {
        if ($confidenceLevel === null || $confidenceLevel < 90) {
            return 'inconclusive';
        }
        
        $difference = abs($rateA - $rateB);
        if ($difference < 0.01) {
            return 'none';
        }
        
        return $rateA > $rateB ? 'a' : 'b';
    }

    /**
     * Complete test.
     *
     * @param  ProductABTest  $test
     * @return ProductABTest
     */
    public function completeTest(ProductABTest $test): ProductABTest
    {
        $this->updateResults($test);
        
        $test->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);
        
        return $test->fresh();
    }
}

