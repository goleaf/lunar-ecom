<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

class PriceMatrix extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'price_matrices';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'name',
        'matrix_type',
        'description',
        'priority',
        'is_active',
        'requires_approval',
        'starts_at',
        'expires_at',
        'rules',
        'allow_mix_match',
        'mix_match_variants',
        'mix_match_min_quantity',
        'min_order_quantity',
        'max_order_quantity',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'rules' => 'array',
        'allow_mix_match' => 'boolean',
        'mix_match_variants' => 'array',
        'mix_match_min_quantity' => 'integer',
        'min_order_quantity' => 'integer',
        'max_order_quantity' => 'integer',
        'approved_at' => 'datetime',
    ];

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
     * Get pricing rules.
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PriceMatrixRule::class, 'price_matrix_id')
            ->orderBy('priority', 'desc');
    }

    /**
     * Get pricing tiers.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(PricingTier::class, 'price_matrix_id')
            ->where('is_active', true)
            ->orderBy('min_quantity');
    }

    /**
     * Get all tiers (including inactive).
     */
    public function allTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class, 'price_matrix_id')
            ->orderBy('min_quantity');
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        $userClass = class_exists(\Lunar\Models\User::class) 
            ? \Lunar\Models\User::class 
            : \App\Models\User::class;
        
        return $this->belongsTo($userClass, 'approved_by');
    }

    /**
     * Check if matrix is currently active.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->requires_approval && $this->approval_status !== 'approved') {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get active matrices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('requires_approval', false)
                  ->orWhere('approval_status', 'approved');
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get quantity-based matrices.
     */
    public function scopeQuantityBased($query)
    {
        return $query->where('matrix_type', 'quantity');
    }

    /**
     * Scope to get customer group matrices.
     */
    public function scopeCustomerGroupBased($query)
    {
        return $query->where('matrix_type', 'customer_group');
    }

    /**
     * Scope to get regional matrices.
     */
    public function scopeRegional($query)
    {
        return $query->where('matrix_type', 'region');
    }

    /**
     * Scope to get pending approval matrices.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('requires_approval', true)
            ->where('approval_status', 'pending');
    }
}
