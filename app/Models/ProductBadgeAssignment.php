<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

class ProductBadgeAssignment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_badge_assignments';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'badge_id',
        'product_id',
        'assignment_type',
        'rule_id',
        'priority',
        'display_position',
        'visibility_rules',
        'starts_at',
        'expires_at',
        'assigned_at',
        'assigned_by',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'visibility_rules' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'assigned_at' => 'datetime',
        'is_active' => 'boolean',
        'priority' => 'integer',
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
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the rule that created this assignment.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ProductBadgeRule::class, 'rule_id');
    }

    /**
     * Get the user who assigned the badge.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }

    /**
     * Check if assignment is currently active.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
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
     * Check if badge should be visible in a specific context.
     */
    public function isVisibleIn(string $context): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $visibilityRules = $this->visibility_rules ?? $this->badge->display_conditions ?? [];

        // If show_everywhere is true, show in all contexts
        if (isset($visibilityRules['show_everywhere']) && $visibilityRules['show_everywhere']) {
            return true;
        }

        // Check specific context
        $contextMap = [
            'category' => 'show_on_category',
            'product' => 'show_on_product',
            'search' => 'show_on_search',
        ];

        $contextKey = $contextMap[$context] ?? null;

        return $contextKey && isset($visibilityRules[$contextKey]) && $visibilityRules[$contextKey];
    }

    /**
     * Scope to get active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
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
     * Scope to get manual assignments.
     */
    public function scopeManual($query)
    {
        return $query->where('assignment_type', 'manual');
    }

    /**
     * Scope to get automatic assignments.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('assignment_type', 'automatic');
    }
}
