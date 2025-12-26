<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\Customer;
use Lunar\Models\Order;

class FitReview extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'fit_reviews';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'customer_id',
        'order_id',
        'purchased_size',
        'recommended_size',
        'height_cm',
        'weight_kg',
        'body_type',
        'fit_rating',
        'would_recommend_size',
        'fit_notes',
        'fit_by_area',
        'is_verified_purchase',
        'is_approved',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'height_cm' => 'integer',
        'weight_kg' => 'integer',
        'fit_by_area' => 'array',
        'would_recommend_size' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($review) {
            if (!$review->reviewed_at) {
                $review->reviewed_at = now();
            }
        });
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope to get approved reviews.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get verified purchases.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope to get reviews for a specific size.
     */
    public function scopeForSize($query, string $size)
    {
        return $query->where('purchased_size', $size);
    }

    /**
     * Scope to get reviews with perfect fit.
     */
    public function scopePerfectFit($query)
    {
        return $query->where('fit_rating', 'perfect');
    }
}


