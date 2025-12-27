<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\B2BContract;

/**
 * Price List Model
 * 
 * Represents a price list associated with a B2B contract.
 * Supports inheritance, versioning, and multiple active price lists.
 */
class PriceList extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'price_lists';

    protected $fillable = [
        'contract_id',
        'name',
        'description',
        'parent_id',
        'version',
        'is_active',
        'valid_from',
        'valid_to',
        'priority',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'priority' => 'integer',
        'meta' => 'array',
    ];

    /**
     * Get the contract that owns this price list.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    /**
     * Get the parent price list (for inheritance).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'parent_id');
    }

    /**
     * Get child price lists (inheriting from this one).
     */
    public function children(): HasMany
    {
        return $this->hasMany(PriceList::class, 'parent_id');
    }

    /**
     * Get all prices in this price list.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ContractPrice::class, 'price_list_id');
    }

    /**
     * Get prices for a specific variant.
     */
    public function pricesForVariant(int $variantId): HasMany
    {
        return $this->prices()
            ->where('product_variant_id', $variantId)
            ->where('pricing_type', 'variant_fixed');
    }

    /**
     * Get prices for a specific category.
     */
    public function pricesForCategory(int $categoryId): HasMany
    {
        return $this->prices()
            ->where('category_id', $categoryId)
            ->where('pricing_type', 'category');
    }

    /**
     * Check if price list is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active
            && $this->contract->isActive()
            && ($this->valid_from === null || $this->valid_from <= now())
            && ($this->valid_to === null || $this->valid_to >= now());
    }

    /**
     * Get inherited prices from parent price list.
     */
    public function getInheritedPrices(): \Illuminate\Support\Collection
    {
        if (!$this->parent_id) {
            return collect();
        }

        $parent = $this->parent;
        if (!$parent) {
            return collect();
        }

        // Get parent prices and merge with this price list's prices
        $parentPrices = $parent->prices;
        $thisPrices = $this->prices;

        // Merge: this price list's prices override parent prices
        return $parentPrices->map(function ($parentPrice) use ($thisPrices) {
            $override = $thisPrices->firstWhere('product_variant_id', $parentPrice->product_variant_id);
            return $override ?? $parentPrice;
        })->merge($thisPrices)->unique('id');
    }

    /**
     * Scope to get active price lists.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            });
    }

    /**
     * Scope to get price lists by priority (highest first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}


