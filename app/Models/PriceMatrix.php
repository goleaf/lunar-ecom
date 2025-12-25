<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use Carbon\Carbon;

/**
 * PriceMatrix model for advanced variant pricing.
 * 
 * Supports:
 * - Quantity-based pricing (tiered pricing)
 * - Customer group pricing
 * - Regional pricing
 * - Mixed pricing rules
 * - Promotional pricing with date ranges
 * 
 * Rules JSON structure examples:
 * 
 * Quantity-based:
 * {
 *   "tiers": [
 *     {"min_quantity": 1, "max_quantity": 10, "price": 10000},
 *     {"min_quantity": 11, "max_quantity": 50, "price": 9000},
 *     {"min_quantity": 51, "price": 8000}
 *   ]
 * }
 * 
 * Customer group:
 * {
 *   "customer_groups": {
 *     "retail": {"price": 10000},
 *     "wholesale": {"price": 8000},
 *     "vip": {"price": 7500}
 *   }
 * }
 * 
 * Regional:
 * {
 *   "regions": {
 *     "US": {"price": 10000},
 *     "EU": {"price": 9000},
 *     "UK": {"price": 8500}
 *   }
 * }
 * 
 * Mixed:
 * {
 *   "conditions": [
 *     {
 *       "quantity": {"min": 11, "max": 50},
 *       "customer_group": "wholesale",
 *       "region": "US",
 *       "price": 7500
 *     }
 *   ]
 * }
 */
class PriceMatrix extends Model
{
    use HasFactory;

    protected $table = 'lunar_price_matrices';

    protected $fillable = [
        'product_id',
        'matrix_type',
        'rules',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
        'description',
    ];

    protected $casts = [
        'rules' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Matrix types
     */
    const TYPE_QUANTITY = 'quantity';
    const TYPE_CUSTOMER_GROUP = 'customer_group';
    const TYPE_REGION = 'region';
    const TYPE_MIXED = 'mixed';

    /**
     * Get the product that owns this price matrix.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get price histories for this matrix.
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'price_matrix_id');
    }

    /**
     * Get pricing approvals for this matrix.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(PricingApproval::class, 'price_matrix_id');
    }

    /**
     * Check if this matrix is currently active (within date range if set).
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to only active matrices.
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Scope by matrix type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('matrix_type', $type);
    }

    /**
     * Scope by product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Get rules for a specific context.
     */
    public function getRulesForContext(
        ?int $quantity = null,
        ?string $customerGroup = null,
        ?string $region = null
    ): ?array {
        $rules = $this->rules;

        if (!$rules) {
            return null;
        }

        switch ($this->matrix_type) {
            case self::TYPE_QUANTITY:
                return $this->getQuantityRules($rules, $quantity);
            
            case self::TYPE_CUSTOMER_GROUP:
                return $this->getCustomerGroupRules($rules, $customerGroup);
            
            case self::TYPE_REGION:
                return $this->getRegionRules($rules, $region);
            
            case self::TYPE_MIXED:
                return $this->getMixedRules($rules, $quantity, $customerGroup, $region);
            
            default:
                return null;
        }
    }

    /**
     * Get quantity-based pricing rule.
     */
    protected function getQuantityRules(array $rules, ?int $quantity): ?array
    {
        if (!$quantity || !isset($rules['tiers'])) {
            return null;
        }

        foreach ($rules['tiers'] as $tier) {
            $min = $tier['min_quantity'] ?? 1;
            $max = $tier['max_quantity'] ?? null;

            if ($quantity >= $min && ($max === null || $quantity <= $max)) {
                return [
                    'price' => $tier['price'],
                    'min_quantity' => $min,
                    'max_quantity' => $max,
                ];
            }
        }

        return null;
    }

    /**
     * Get customer group pricing rule.
     */
    protected function getCustomerGroupRules(array $rules, ?string $customerGroup): ?array
    {
        if (!$customerGroup || !isset($rules['customer_groups'][$customerGroup])) {
            return null;
        }

        return $rules['customer_groups'][$customerGroup];
    }

    /**
     * Get regional pricing rule.
     */
    protected function getRegionRules(array $rules, ?string $region): ?array
    {
        if (!$region || !isset($rules['regions'][$region])) {
            return null;
        }

        return $rules['regions'][$region];
    }

    /**
     * Get mixed pricing rule.
     */
    protected function getMixedRules(
        array $rules,
        ?int $quantity,
        ?string $customerGroup,
        ?string $region
    ): ?array {
        if (!isset($rules['conditions'])) {
            return null;
        }

        foreach ($rules['conditions'] as $condition) {
            $matches = true;

            // Check quantity
            if (isset($condition['quantity'])) {
                $qtyMin = $condition['quantity']['min'] ?? 1;
                $qtyMax = $condition['quantity']['max'] ?? null;
                
                if ($quantity === null || $quantity < $qtyMin) {
                    $matches = false;
                }
                if ($qtyMax !== null && $quantity > $qtyMax) {
                    $matches = false;
                }
            }

            // Check customer group
            if (isset($condition['customer_group']) && $condition['customer_group'] !== $customerGroup) {
                $matches = false;
            }

            // Check region
            if (isset($condition['region']) && $condition['region'] !== $region) {
                $matches = false;
            }

            if ($matches) {
                return [
                    'price' => $condition['price'],
                    'quantity' => $condition['quantity'] ?? null,
                    'customer_group' => $condition['customer_group'] ?? null,
                    'region' => $condition['region'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Get all tier pricing information.
     */
    public function getTierPricing(): array
    {
        $rules = $this->rules;
        
        if ($this->matrix_type !== self::TYPE_QUANTITY || !isset($rules['tiers'])) {
            return [];
        }

        return $rules['tiers'];
    }

    /**
     * Get volume discounts.
     */
    public function getVolumeDiscounts(int $basePrice): array
    {
        $tiers = $this->getTierPricing();
        $discounts = [];

        foreach ($tiers as $tier) {
            $tierPrice = $tier['price'] ?? $basePrice;
            $discount = $basePrice - $tierPrice;
            $discountPercent = $basePrice > 0 ? round(($discount / $basePrice) * 100, 2) : 0;

            $discounts[] = [
                'min_quantity' => $tier['min_quantity'] ?? 1,
                'max_quantity' => $tier['max_quantity'] ?? null,
                'price' => $tierPrice,
                'discount' => $discount,
                'discount_percent' => $discountPercent,
            ];
        }

        return $discounts;
    }
}
