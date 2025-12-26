<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for variant relationships.
 * 
 * Links variants together for various purposes:
 * - Cross-variant linking (same product, different attributes)
 * - Replacement variants
 * - Upgrade / downgrade variants
 * - Accessory variants
 * - Bundle component variants
 */
class VariantRelationship extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_relationships';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'variant_id',
        'related_variant_id',
        'relationship_type',
        'label',
        'description',
        'sort_order',
        'is_active',
        'is_bidirectional',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_bidirectional' => 'boolean',
        'metadata' => 'array',
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
     * Related variant relationship.
     *
     * @return BelongsTo
     */
    public function relatedVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'related_variant_id');
    }

    /**
     * Scope active relationships.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by relationship type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Get reverse relationship (if bidirectional).
     *
     * @return VariantRelationship|null
     */
    public function getReverseRelationship(): ?VariantRelationship
    {
        if (!$this->is_bidirectional) {
            return null;
        }

        return static::where('variant_id', $this->related_variant_id)
            ->where('related_variant_id', $this->variant_id)
            ->where('relationship_type', $this->relationship_type)
            ->first();
    }
}


