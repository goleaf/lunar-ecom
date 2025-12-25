<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProductABTest model for A/B testing.
 */
class ProductABTest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_ab_tests';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'product_id',
        'variant_a_id',
        'variant_b_id',
        'test_type',
        'variant_a_config',
        'variant_b_config',
        'traffic_split_a',
        'traffic_split_b',
        'status',
        'started_at',
        'ended_at',
        'scheduled_start_at',
        'scheduled_end_at',
        'visitors_a',
        'visitors_b',
        'conversions_a',
        'conversions_b',
        'conversion_rate_a',
        'conversion_rate_b',
        'revenue_a',
        'revenue_b',
        'confidence_level',
        'winner',
        'min_sample_size',
        'min_duration_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variant_a_config' => 'array',
        'variant_b_config' => 'array',
        'traffic_split_a' => 'integer',
        'traffic_split_b' => 'integer',
        'visitors_a' => 'integer',
        'visitors_b' => 'integer',
        'conversions_a' => 'integer',
        'conversions_b' => 'integer',
        'conversion_rate_a' => 'decimal:4',
        'conversion_rate_b' => 'decimal:4',
        'revenue_a' => 'decimal:2',
        'revenue_b' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'min_sample_size' => 'integer',
        'min_duration_days' => 'integer',
    ];

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variant A relationship.
     *
     * @return BelongsTo
     */
    public function variantA(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_a_id');
    }

    /**
     * Variant B relationship.
     *
     * @return BelongsTo
     */
    public function variantB(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_b_id');
    }

    /**
     * Test events relationship.
     *
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(\App\Models\ABTestEvent::class, 'ab_test_id');
    }

    /**
     * Scope to get running tests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope to get completed tests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}

