<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\ProductVariant;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use App\Models\PriceList;

/**
 * Contract Price Model
 * 
 * Represents pricing rules within a price list.
 * Supports variant fixed prices, category pricing, margin-based pricing,
 * quantity breaks, and price floors/ceilings.
 */
class ContractPrice extends Model
{
    use HasFactory;

    protected $table = 'lunar_contract_prices';

    protected $fillable = [
        'price_list_id',
        'pricing_type',
        'product_variant_id',
        'category_id',
        'fixed_price',
        'margin_percentage',
        'margin_amount',
        'quantity_break',
        'min_quantity',
        'price_floor',
        'price_ceiling',
        'currency_id',
        'meta',
    ];

    protected $casts = [
        'fixed_price' => 'integer',
        'margin_percentage' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'quantity_break' => 'integer',
        'min_quantity' => 'integer',
        'price_floor' => 'integer',
        'price_ceiling' => 'integer',
        'meta' => 'array',
    ];

    // Pricing type constants
    const TYPE_VARIANT_FIXED = 'variant_fixed';
    const TYPE_CATEGORY = 'category';
    const TYPE_MARGIN_BASED = 'margin_based';

    /**
     * Get the price list that owns this price.
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    /**
     * Get the product variant (for variant_fixed pricing).
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the category (for category pricing).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'category_id');
    }

    /**
     * Get the currency.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Calculate price for a given variant and quantity.
     * 
     * @param ProductVariant $variant
     * @param int $quantity
     * @param int|null $basePrice Base price in minor currency units
     * @return int|null Calculated price in minor currency units, or null if not applicable
     */
    public function calculatePrice(ProductVariant $variant, int $quantity, ?int $basePrice = null): ?int
    {
        // Check minimum quantity
        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return null;
        }

        // Check quantity break
        if ($this->quantity_break && $quantity < $this->quantity_break) {
            return null;
        }

        $price = null;

        switch ($this->pricing_type) {
            case self::TYPE_VARIANT_FIXED:
                if ($this->product_variant_id === $variant->id) {
                    $price = $this->fixed_price;
                }
                break;

            case self::TYPE_CATEGORY:
                // Check if variant's product belongs to this category
                if ($this->category_id && $variant->product) {
                    $productCategories = $variant->product->collections()->pluck('id')->toArray();
                    if (in_array($this->category_id, $productCategories)) {
                        $price = $this->fixed_price;
                    }
                }
                break;

            case self::TYPE_MARGIN_BASED:
                if ($basePrice === null) {
                    // Get base price from variant
                    $basePrice = $variant->getEffectivePrice($quantity) ?? 0;
                }
                
                if ($basePrice > 0) {
                    $margin = 0;
                    
                    if ($this->margin_percentage !== null) {
                        $margin = ($basePrice * $this->margin_percentage) / 100;
                    } elseif ($this->margin_amount !== null) {
                        $margin = $this->margin_amount * 100; // Convert to minor units
                    }
                    
                    $price = $basePrice + $margin;
                }
                break;
        }

        if ($price === null) {
            return null;
        }

        // Apply price floor
        if ($this->price_floor && $price < $this->price_floor) {
            $price = $this->price_floor;
        }

        // Apply price ceiling
        if ($this->price_ceiling && $price > $this->price_ceiling) {
            $price = $this->price_ceiling;
        }

        return (int) round($price);
    }

    /**
     * Scope to get prices for a variant.
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('product_variant_id', $variantId)
            ->where('pricing_type', self::TYPE_VARIANT_FIXED);
    }

    /**
     * Scope to get prices for a category.
     */
    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId)
            ->where('pricing_type', self::TYPE_CATEGORY);
    }

    /**
     * Scope to get prices matching quantity break.
     */
    public function scopeMatchingQuantity($query, int $quantity)
    {
        return $query->where(function ($q) use ($quantity) {
            $q->whereNull('quantity_break')
                ->orWhere('quantity_break', '<=', $quantity);
        })->where(function ($q) use ($quantity) {
            $q->whereNull('min_quantity')
                ->orWhere('min_quantity', '<=', $quantity);
        });
    }
}


