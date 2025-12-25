<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBadgePerformance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_badge_performance';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'badge_id',
        'product_id',
        'views',
        'clicks',
        'add_to_cart',
        'purchases',
        'revenue',
        'click_through_rate',
        'conversion_rate',
        'add_to_cart_rate',
        'period_start',
        'period_end',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'views' => 'integer',
        'clicks' => 'integer',
        'add_to_cart' => 'integer',
        'purchases' => 'integer',
        'revenue' => 'decimal:2',
        'click_through_rate' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'add_to_cart_rate' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    /**
     * Get the badge.
     */
    public function badge(): BelongsTo
    {
        return $this->belongsTo(ProductBadge::class, 'badge_id');
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    /**
     * Calculate and update conversion rates.
     */
    public function calculateRates(): void
    {
        $this->click_through_rate = $this->views > 0 
            ? round(($this->clicks / $this->views) * 100, 2) 
            : 0;

        $this->conversion_rate = $this->views > 0 
            ? round(($this->purchases / $this->views) * 100, 2) 
            : 0;

        $this->add_to_cart_rate = $this->views > 0 
            ? round(($this->add_to_cart / $this->views) * 100, 2) 
            : 0;

        $this->save();
    }

    /**
     * Increment a metric.
     */
    public function incrementMetric(string $metric, int $amount = 1): void
    {
        $this->increment($metric, $amount);
        $this->calculateRates();
    }
}
