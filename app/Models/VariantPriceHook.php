<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Model for dynamic pricing hooks.
 */
class VariantPriceHook extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_price_hooks';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'variant_id',
        'hook_type',
        'hook_identifier',
        'config',
        'priority',
        'is_active',
        'last_executed_at',
        'cache_duration',
        'cached_price',
        'cached_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
        'cache_duration' => 'integer',
        'cached_price' => 'integer',
        'cached_at' => 'datetime',
    ];

    /**
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Check if cached price is still valid.
     *
     * @return bool
     */
    public function isCacheValid(): bool
    {
        if (!$this->cached_at || !$this->cached_price) {
            return false;
        }

        $expiresAt = $this->cached_at->addSeconds($this->cache_duration);
        return Carbon::now()->lt($expiresAt);
    }

    /**
     * Get cached price if valid.
     *
     * @return int|null
     */
    public function getCachedPrice(): ?int
    {
        if ($this->isCacheValid()) {
            return $this->cached_price;
        }

        return null;
    }

    /**
     * Update cached price.
     *
     * @param  int  $price
     * @return void
     */
    public function updateCache(int $price): void
    {
        $this->update([
            'cached_price' => $price,
            'cached_at' => Carbon::now(),
            'last_executed_at' => Carbon::now(),
        ]);
    }

    /**
     * Scope active hooks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by hook type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('hook_type', $type);
    }
}

