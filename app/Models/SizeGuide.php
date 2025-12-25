<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product;

/**
 * Size Guide Model
 * 
 * Represents a size guide with measurement charts for products.
 * Can be associated with multiple products and contains size measurements.
 */
class SizeGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_type', // e.g., 'clothing', 'shoes', 'accessories'
        'gender', // 'men', 'women', 'unisex', 'kids'
        'is_active',
        'display_order',
        'measurement_unit', // 'cm', 'inches', 'both'
        'size_chart_data', // JSON field for size measurements
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'size_chart_data' => 'array',
    ];

    /**
     * Products that use this size guide.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_size_guides',
            'size_guide_id',
            'product_id'
        )->withTimestamps();
    }

    /**
     * Fit feedback entries for this size guide.
     */
    public function fitFeedbacks(): HasMany
    {
        return $this->hasMany(FitFeedback::class, 'size_guide_id');
    }

    /**
     * Scope to get active size guides.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category type.
     */
    public function scopeByCategoryType($query, string $categoryType)
    {
        return $query->where('category_type', $categoryType);
    }

    /**
     * Scope to filter by gender.
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Get size chart as formatted array.
     */
    public function getSizeChart(): array
    {
        return $this->size_chart_data ?? [];
    }

    /**
     * Get available sizes from chart data.
     */
    public function getAvailableSizes(): array
    {
        $chart = $this->getSizeChart();
        if (empty($chart) || !isset($chart['sizes'])) {
            return [];
        }

        return array_column($chart['sizes'], 'size');
    }
}

