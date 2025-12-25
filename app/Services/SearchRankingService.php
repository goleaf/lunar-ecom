<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for calculating product popularity scores and ranking.
 */
class SearchRankingService
{
    /**
     * Calculate popularity score for a product.
     *
     * Formula considers:
     * - View count (weight: 1)
     * - Order count (weight: 5)
     * - Review count (weight: 3)
     * - Average rating (weight: 2)
     * - Recency (decay factor)
     *
     * @param  Product  $product
     * @return float
     */
    public function calculatePopularityScore(Product $product): float
    {
        $viewWeight = 1;
        $orderWeight = 5;
        $reviewWeight = 3;
        $ratingWeight = 2;
        
        // Base scores
        $viewScore = ($product->view_count ?? 0) * $viewWeight;
        $orderScore = ($product->order_count ?? 0) * $orderWeight;
        $reviewScore = ($product->total_reviews ?? 0) * $reviewWeight;
        $ratingScore = ($product->average_rating ?? 0) * $ratingWeight * 20; // Scale rating (0-5) to 0-100
        
        // Recency decay (products updated recently get bonus)
        $recencyBonus = 0;
        if ($product->updated_at) {
            $daysSinceUpdate = $product->updated_at->diffInDays(now());
            // Bonus decreases over 90 days
            $recencyBonus = max(0, (90 - $daysSinceUpdate) / 90 * 10);
        }
        
        // Calculate total score
        $totalScore = $viewScore + $orderScore + $reviewScore + $ratingScore + $recencyBonus;
        
        // Normalize to 0-1000 range
        $normalizedScore = min(1000, $totalScore);
        
        return round($normalizedScore, 4);
    }

    /**
     * Update popularity score for a product.
     *
     * @param  Product  $product
     * @return void
     */
    public function updatePopularityScore(Product $product): void
    {
        $score = $this->calculatePopularityScore($product);
        
        $product->updateQuietly([
            'popularity_score' => $score,
            'popularity_updated_at' => now(),
        ]);
    }

    /**
     * Update popularity scores for all products.
     *
     * @param  int  $chunkSize
     * @return int  Number of products updated
     */
    public function updateAllPopularityScores(int $chunkSize = 100): int
    {
        $count = 0;
        
        Product::chunk($chunkSize, function ($products) use (&$count) {
            foreach ($products as $product) {
                $this->updatePopularityScore($product);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Get ranking rules for search.
     *
     * @return array
     */
    public function getRankingRules(): array
    {
        return [
            'popularity_score:desc', // Higher popularity first
            'order_count:desc',     // More orders first
            'average_rating:desc',  // Higher ratings first
            'total_reviews:desc',   // More reviews first
            'updated_at:desc',      // Recently updated first
        ];
    }

    /**
     * Apply ranking rules to a query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array|null  $customRules
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyRankingRules($query, ?array $customRules = null)
    {
        $rules = $customRules ?? $this->getRankingRules();
        
        foreach ($rules as $rule) {
            [$field, $direction] = explode(':', $rule);
            $query->orderBy($field, $direction);
        }
        
        return $query;
    }

    /**
     * Get popular products.
     *
     * @param  int  $limit
     * @param  string  $period  'day', 'week', 'month', 'all'
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularProducts(int $limit = 10, string $period = 'all')
    {
        $cacheKey = "popular_products.{$period}.{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($limit, $period) {
            $query = Product::published()
                ->where('popularity_score', '>', 0)
                ->orderBy('popularity_score', 'desc')
                ->limit($limit);
            
            // Filter by period if needed
            if ($period !== 'all') {
                $dateField = match($period) {
                    'day' => now()->subDay(),
                    'week' => now()->subWeek(),
                    'month' => now()->subMonth(),
                    default => null,
                };
                
                if ($dateField) {
                    $query->where('popularity_updated_at', '>=', $dateField);
                }
            }
            
            return $query->get();
        });
    }

    /**
     * Increment view count for a product.
     *
     * @param  Product  $product
     * @return void
     */
    public function incrementViewCount(Product $product): void
    {
        $product->increment('view_count');
        
        // Update popularity score periodically (every 10 views)
        if ($product->view_count % 10 === 0) {
            $this->updatePopularityScore($product);
        }
    }

    /**
     * Increment order count for a product.
     *
     * @param  Product  $product
     * @return void
     */
    public function incrementOrderCount(Product $product): void
    {
        $product->increment('order_count');
        
        // Always update popularity when order count changes
        $this->updatePopularityScore($product);
    }

    /**
     * Get ranking statistics.
     *
     * @return array
     */
    public function getRankingStatistics(): array
    {
        return Cache::remember('ranking_statistics', 3600, function () {
            return [
                'total_products' => Product::count(),
                'products_with_score' => Product::where('popularity_score', '>', 0)->count(),
                'average_score' => Product::where('popularity_score', '>', 0)->avg('popularity_score'),
                'max_score' => Product::max('popularity_score'),
                'top_product' => Product::orderBy('popularity_score', 'desc')->first(['id', 'popularity_score']),
            ];
        });
    }
}

