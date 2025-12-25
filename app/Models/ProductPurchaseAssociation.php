<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductPurchaseAssociation model for tracking product co-purchase patterns.
 */
class ProductPurchaseAssociation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_purchase_associations';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'associated_product_id',
        'co_purchase_count',
        'confidence',
        'support',
        'lift',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'confidence' => 'decimal:4',
        'support' => 'decimal:4',
        'lift' => 'decimal:4',
    ];

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Associated product relationship.
     *
     * @return BelongsTo
     */
    public function associatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'associated_product_id');
    }

    /**
     * Scope to get top associations by co-purchase count.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopAssociations($query, int $limit = 10)
    {
        return $query->orderByDesc('co_purchase_count')
            ->orderByDesc('confidence')
            ->limit($limit);
    }

    /**
     * Scope to get associations with high confidence.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $minConfidence
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighConfidence($query, float $minConfidence = 0.3)
    {
        return $query->where('confidence', '>=', $minConfidence);
    }
}
