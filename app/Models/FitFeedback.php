<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;
use Lunar\Models\Customer;

/**
 * Fit Feedback Model
 * 
 * Stores customer feedback about product fit to help reduce returns.
 */
class FitFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size_guide_id',
        'fit_finder_quiz_id',
        'customer_id',
        'order_id', // Optional: link to order if available
        'purchased_size',
        'recommended_size', // Size recommended by fit finder
        'actual_fit', // 'perfect', 'too_small', 'too_large', 'too_tight', 'too_loose'
        'fit_rating', // 1-5 rating
        'body_measurements', // JSON field for customer measurements
        'feedback_text',
        'would_exchange', // Would they exchange for different size
        'would_return', // Would they return the product
        'is_helpful', // Admin can mark if feedback is helpful
        'is_public', // Can be shown to other customers
    ];

    protected $casts = [
        'fit_rating' => 'integer',
        'body_measurements' => 'array',
        'would_exchange' => 'boolean',
        'would_return' => 'boolean',
        'is_helpful' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Product this feedback is for.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Size guide used.
     */
    public function sizeGuide(): BelongsTo
    {
        return $this->belongsTo(SizeGuide::class, 'size_guide_id');
    }

    /**
     * Fit finder quiz used.
     */
    public function fitFinderQuiz(): BelongsTo
    {
        return $this->belongsTo(FitFinderQuiz::class, 'fit_finder_quiz_id');
    }

    /**
     * Customer who provided feedback.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Scope to get helpful feedback.
     */
    public function scopeHelpful($query)
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Scope to get public feedback.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter by fit rating.
     */
    public function scopeByFitRating($query, int $rating)
    {
        return $query->where('fit_rating', $rating);
    }

    /**
     * Scope to filter by actual fit.
     */
    public function scopeByActualFit($query, string $fit)
    {
        return $query->where('actual_fit', $fit);
    }

    /**
     * Get fit statistics for a product.
     */
    public static function getFitStatistics(int $productId): array
    {
        $feedbacks = self::where('product_id', $productId)->get();

        if ($feedbacks->isEmpty()) {
            return [
                'total_feedbacks' => 0,
                'average_rating' => 0,
                'fit_distribution' => [],
                'return_rate' => 0,
                'exchange_rate' => 0,
            ];
        }

        return [
            'total_feedbacks' => $feedbacks->count(),
            'average_rating' => round($feedbacks->avg('fit_rating'), 2),
            'fit_distribution' => $feedbacks->groupBy('actual_fit')->map->count(),
            'return_rate' => round(($feedbacks->where('would_return', true)->count() / $feedbacks->count()) * 100, 2),
            'exchange_rate' => round(($feedbacks->where('would_exchange', true)->count() / $feedbacks->count()) * 100, 2),
        ];
    }
}

