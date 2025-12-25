<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBadgeRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_badge_rules';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'badge_id',
        'condition_type',
        'name',
        'description',
        'conditions',
        'priority',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'conditions' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the badge that owns the rule.
     */
    public function badge(): BelongsTo
    {
        return $this->belongsTo(ProductBadge::class, 'badge_id');
    }

    /**
     * Get assignments created by this rule.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ProductBadgeAssignment::class, 'rule_id');
    }

    /**
     * Check if rule is currently active.
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
     * Scope to get active rules.
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
     * Scope to get automatic rules.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('condition_type', 'automatic');
    }

    /**
     * Scope to get manual rules.
     */
    public function scopeManual($query)
    {
        return $query->where('condition_type', 'manual');
    }
}
