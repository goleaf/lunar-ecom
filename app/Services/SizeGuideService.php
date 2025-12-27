<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SizeGuide;
use App\Models\SizeChart;
use App\Models\FitReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SizeGuideService
{
    /**
     * Get size guide for a product.
     *
     * @param  Product  $product
     * @param  string|null  $region
     * @return SizeGuide|null
     */
    public function getSizeGuide(Product $product, ?string $region = null): ?SizeGuide
    {
        // First, check if product has a direct size guide assignment
        $pivotTable = config('lunar.database.table_prefix') . 'product_size_guide';

        $sizeGuide = $product->sizeGuides()
            ->when($region, function ($query) use ($region, $pivotTable) {
                $query->where(function ($query) use ($region, $pivotTable) {
                    $query->where("{$pivotTable}.region", $region)
                        ->orWhereNull("{$pivotTable}.region");
                });
            })
            ->orderByPivot('priority', 'desc')
            ->active()
            ->first();

        if ($sizeGuide) {
            return $sizeGuide;
        }

        // Check category-based size guide
        $category = $product->collections()->first();
        if ($category) {
            $sizeGuide = SizeGuide::where('category_id', $category->id)
                ->forRegion($region)
                ->active()
                ->orderBy('display_order')
                ->first();

            if ($sizeGuide) {
                return $sizeGuide;
            }
        }

        // Check brand-based size guide
        if ($product->brand) {
            $sizeGuide = SizeGuide::where('brand_id', $product->brand->id)
                ->forRegion($region)
                ->active()
                ->orderBy('display_order')
                ->first();

            if ($sizeGuide) {
                return $sizeGuide;
            }
        }

        return null;
    }

    /**
     * Get size recommendation based on measurements.
     *
     * @param  SizeGuide  $sizeGuide
     * @param  array  $measurements  ['chest' => 38, 'waist' => 32, 'hips' => 40, etc.]
     * @return array
     */
    public function getSizeRecommendation(SizeGuide $sizeGuide, array $measurements): array
    {
        $sizeCharts = $sizeGuide->sizeCharts;
        $scores = [];

        foreach ($sizeCharts as $chart) {
            $score = 0;
            $matchedMeasurements = 0;
            $totalMeasurements = 0;

            foreach ($measurements as $key => $value) {
                if ($value === null || $value <= 0) {
                    continue;
                }

                $totalMeasurements++;
                $chartMeasurement = $chart->getMeasurement($key);

                if ($chartMeasurement !== null) {
                    $matchedMeasurements++;
                    $difference = abs($chartMeasurement - $value);
                    
                    // Calculate score (closer = higher score)
                    // Perfect match = 100, within 2cm = 80, within 5cm = 50, etc.
                    if ($difference <= 1) {
                        $score += 100;
                    } elseif ($difference <= 2) {
                        $score += 80;
                    } elseif ($difference <= 5) {
                        $score += 50;
                    } elseif ($difference <= 10) {
                        $score += 20;
                    } else {
                        $score += max(0, 10 - ($difference / 5));
                    }
                }
            }

            if ($totalMeasurements > 0) {
                $averageScore = $score / $totalMeasurements;
                $matchPercentage = ($matchedMeasurements / $totalMeasurements) * 100;

                $scores[] = [
                    'size' => $chart->size_name,
                    'size_chart' => $chart,
                    'score' => $averageScore,
                    'match_percentage' => $matchPercentage,
                    'matched_measurements' => $matchedMeasurements,
                    'total_measurements' => $totalMeasurements,
                ];
            }
        }

        // Sort by score descending
        usort($scores, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $scores;
    }

    /**
     * Get size recommendation using fit reviews and measurements.
     *
     * @param  Product  $product
     * @param  array  $measurements
     * @param  array  $bodyInfo  ['height_cm' => 175, 'weight_kg' => 70, 'body_type' => 'regular']
     * @return array
     */
    public function getSizeRecommendationWithReviews(Product $product, array $measurements, array $bodyInfo = []): array
    {
        $sizeGuide = $this->getSizeGuide($product);
        
        if (!$sizeGuide) {
            return [];
        }

        // Get base recommendation from measurements
        $baseRecommendations = $this->getSizeRecommendation($sizeGuide, $measurements);

        // Get fit reviews for this product
        $fitReviews = FitReview::where('product_id', $product->id)
            ->approved()
            ->get();

        // Enhance recommendations with fit review data
        foreach ($baseRecommendations as &$recommendation) {
            $size = $recommendation['size'];
            
            // Get reviews for this size
            $sizeReviews = $fitReviews->where('purchased_size', $size);
            
            if ($sizeReviews->isEmpty()) {
                continue;
            }

            // Calculate fit statistics
            $totalReviews = $sizeReviews->count();
            $perfectFitCount = $sizeReviews->where('fit_rating', 'perfect')->count();
            $recommendCount = $sizeReviews->where('would_recommend_size', true)->count();
            
            $perfectFitPercentage = ($perfectFitCount / $totalReviews) * 100;
            $recommendPercentage = ($recommendCount / $totalReviews) * 100;

            // Adjust score based on fit reviews
            $reviewBonus = ($perfectFitPercentage * 0.3) + ($recommendPercentage * 0.2);
            $recommendation['score'] += $reviewBonus;
            $recommendation['fit_stats'] = [
                'total_reviews' => $totalReviews,
                'perfect_fit_percentage' => round($perfectFitPercentage, 1),
                'recommend_percentage' => round($recommendPercentage, 1),
            ];

            // If body info provided, find similar body types
            if (!empty($bodyInfo['height_cm']) || !empty($bodyInfo['weight_kg']) || !empty($bodyInfo['body_type'])) {
                $similarReviews = $sizeReviews->filter(function ($review) use ($bodyInfo) {
                    $match = true;
                    
                    if (isset($bodyInfo['height_cm']) && $review->height_cm) {
                        $match = $match && abs($review->height_cm - $bodyInfo['height_cm']) <= 5;
                    }
                    
                    if (isset($bodyInfo['weight_kg']) && $review->weight_kg) {
                        $match = $match && abs($review->weight_kg - $bodyInfo['weight_kg']) <= 5;
                    }
                    
                    if (isset($bodyInfo['body_type']) && $review->body_type) {
                        $match = $match && $review->body_type === $bodyInfo['body_type'];
                    }
                    
                    return $match;
                });

                if ($similarReviews->isNotEmpty()) {
                    $similarPerfectFit = $similarReviews->where('fit_rating', 'perfect')->count();
                    $similarPerfectFitPercentage = ($similarPerfectFit / $similarReviews->count()) * 100;
                    
                    $recommendation['similar_body_stats'] = [
                        'count' => $similarReviews->count(),
                        'perfect_fit_percentage' => round($similarPerfectFitPercentage, 1),
                    ];
                    
                    // Additional bonus for similar body types
                    $recommendation['score'] += $similarPerfectFitPercentage * 0.2;
                }
            }
        }

        // Re-sort by updated score
        usort($baseRecommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $baseRecommendations;
    }

    /**
     * Record a fit review.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return FitReview
     */
    public function recordFitReview(Product $product, array $data): FitReview
    {
        return DB::transaction(function () use ($product, $data) {
            $review = FitReview::create([
                'product_id' => $product->id,
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? auth()->user()?->customer?->id,
                'order_id' => $data['order_id'] ?? null,
                'purchased_size' => $data['purchased_size'],
                'recommended_size' => $data['recommended_size'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'body_type' => $data['body_type'] ?? null,
                'fit_rating' => $data['fit_rating'],
                'would_recommend_size' => $data['would_recommend_size'] ?? true,
                'fit_notes' => $data['fit_notes'] ?? null,
                'fit_by_area' => $data['fit_by_area'] ?? null,
                'is_verified_purchase' => !empty($data['order_id']),
                'is_approved' => $data['auto_approve'] ?? true,
            ]);

            return $review;
        });
    }

    /**
     * Get fit statistics for a product.
     *
     * @param  Product  $product
     * @param  string|null  $size
     * @return array
     */
    public function getFitStatistics(Product $product, ?string $size = null): array
    {
        $query = FitReview::where('product_id', $product->id)
            ->approved();

        if ($size) {
            $query->forSize($size);
        }

        $reviews = $query->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'fit_distribution' => [],
                'recommend_percentage' => 0,
                'true_to_size_percentage' => 0,
            ];
        }

        $total = $reviews->count();
        $fitDistribution = [
            'too_small' => $reviews->where('fit_rating', 'too_small')->count(),
            'slightly_small' => $reviews->where('fit_rating', 'slightly_small')->count(),
            'perfect' => $reviews->where('fit_rating', 'perfect')->count(),
            'slightly_large' => $reviews->where('fit_rating', 'slightly_large')->count(),
            'too_large' => $reviews->where('fit_rating', 'too_large')->count(),
        ];

        $recommendCount = $reviews->where('would_recommend_size', true)->count();
        $recommendPercentage = ($recommendCount / $total) * 100;

        // "True to size" = perfect fit percentage
        $trueToSizePercentage = ($fitDistribution['perfect'] / $total) * 100;

        return [
            'total_reviews' => $total,
            'fit_distribution' => array_map(function ($count) use ($total) {
                return [
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                ];
            }, $fitDistribution),
            'recommend_percentage' => round($recommendPercentage, 1),
            'true_to_size_percentage' => round($trueToSizePercentage, 1),
        ];
    }

    /**
     * Get fit distribution by size.
     *
     * @param  Product  $product
     * @return array
     */
    public function getFitDistributionBySize(Product $product): array
    {
        $reviews = FitReview::where('product_id', $product->id)
            ->approved()
            ->get()
            ->groupBy('purchased_size');

        $distribution = [];

        foreach ($reviews as $size => $sizeReviews) {
            $total = $sizeReviews->count();
            $distribution[$size] = [
                'total' => $total,
                'too_small' => $sizeReviews->where('fit_rating', 'too_small')->count(),
                'slightly_small' => $sizeReviews->where('fit_rating', 'slightly_small')->count(),
                'perfect' => $sizeReviews->where('fit_rating', 'perfect')->count(),
                'slightly_large' => $sizeReviews->where('fit_rating', 'slightly_large')->count(),
                'too_large' => $sizeReviews->where('fit_rating', 'too_large')->count(),
                'perfect_percentage' => $total > 0 ? round(($sizeReviews->where('fit_rating', 'perfect')->count() / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }
}

