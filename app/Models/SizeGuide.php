<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Collection;
use Lunar\Models\Brand;

class SizeGuide extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'size_guides';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'measurement_unit',
        'category_id',
        'brand_id',
        'region',
        'supported_regions',
        'size_system',
        'size_labels',
        'is_active',
        'display_order',
        'image',
        'conversion_table',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'supported_regions' => 'array',
        'size_labels' => 'array',
        'conversion_table' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'category_id');
    }

    /**
     * Get the brand.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the size charts.
     */
    public function sizeCharts(): HasMany
    {
        return $this->hasMany(SizeChart::class, 'size_guide_id')
            ->where('is_active', true)
            ->orderBy('size_order');
    }

    /**
     * Get all size charts (including inactive).
     */
    public function allSizeCharts(): HasMany
    {
        return $this->hasMany(SizeChart::class, 'size_guide_id')
            ->orderBy('size_order');
    }

    /**
     * Get products using this size guide.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Product::class,
            config('lunar.database.table_prefix') . 'product_size_guide',
            'size_guide_id',
            'product_id'
        )->withPivot(['region', 'priority'])
          ->withTimestamps();
    }

    /**
     * Scope to get active guides.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get guides for a region.
     */
    public function scopeForRegion($query, ?string $region = null)
    {
        if (!$region) {
            return $query;
        }

        return $query->where(function ($q) use ($region) {
            $q->where('region', $region)
              ->orWhereJsonContains('supported_regions', $region)
              ->orWhereNull('region'); // Global guides
        });
    }

    /**
     * Get size chart for a specific size.
     */
    public function getSizeChart(string $sizeName): ?SizeChart
    {
        return $this->sizeCharts()->where('size_name', $sizeName)->first();
    }

    /**
     * Convert size between systems.
     */
    public function convertSize(string $size, string $fromSystem, string $toSystem): ?string
    {
        $conversionTable = $this->conversion_table ?? [];

        if (!isset($conversionTable[$fromSystem]) || !isset($conversionTable[$fromSystem][$size])) {
            return null;
        }

        $conversions = $conversionTable[$fromSystem][$size] ?? [];

        return $conversions[$toSystem] ?? null;
    }
}
