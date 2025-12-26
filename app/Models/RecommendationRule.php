<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RecommendationRule model for manual recommendation rules.
 */
class RecommendationRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'recommendation_rules';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_product_id',
        'recommended_product_id',
        'rule_type',
        'name',
        'description',
        'conditions',
        'priority',
        'is_active',
        'display_count',
        'click_count',
        'conversion_rate',
        'ab_test_variant',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'conversion_rate' => 'decimal:4',
    ];

    /**
     * Source product relationship.
     *
     * @return BelongsTo
     */
    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    /**
     * Recommended product relationship.
     *
     * @return BelongsTo
     */
    public function recommendedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'recommended_product_id');
    }

    /**
     * Scope to get active rules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rules by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope to order by priority.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderByDesc('priority')
            ->orderByDesc('conversion_rate');
    }

    /**
     * Increment display count.
     *
     * @return void
     */
    public function incrementDisplay(): void
    {
        $this->increment('display_count');
        $this->updateConversionRate();
    }

    /**
     * Increment click count.
     *
     * @return void
     */
    public function incrementClick(): void
    {
        $this->increment('click_count');
        $this->updateConversionRate();
    }

    /**
     * Update conversion rate.
     *
     * @return void
     */
    protected function updateConversionRate(): void
    {
        if ($this->display_count > 0) {
            $this->conversion_rate = round($this->click_count / $this->display_count, 4);
            $this->saveQuietly();
        }
    }
}


