<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attribute Set model.
 * 
 * Represents a set of attribute groups assigned to a product type.
 * Supports inheritance and conditional visibility.
 */
class AttributeSet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'attribute_sets';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'handle',
        'code',
        'description',
        'product_type_id',
        'parent_set_id',
        'is_active',
        'is_default',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Product type relationship.
     *
     * @return BelongsTo
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductType::class);
    }

    /**
     * Parent set relationship (inheritance).
     *
     * @return BelongsTo
     */
    public function parentSet(): BelongsTo
    {
        return $this->belongsTo(AttributeSet::class, 'parent_set_id');
    }

    /**
     * Child sets relationship (inheritance).
     *
     * @return HasMany
     */
    public function childSets(): HasMany
    {
        return $this->hasMany(AttributeSet::class, 'parent_set_id');
    }

    /**
     * Attribute groups relationship.
     *
     * @return BelongsToMany
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeGroup::class,
            config('lunar.database.table_prefix') . 'attribute_set_groups',
            'attribute_set_id',
            'attribute_group_id'
        )
        ->withPivot(['position', 'visibility_conditions', 'is_visible', 'is_collapsible', 'is_collapsed_by_default'])
        ->withTimestamps()
        ->orderBy('position');
    }

    /**
     * Get all attributes (including inherited).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllAttributes(): \Illuminate\Support\Collection
    {
        $attributes = collect();

        // Get attributes from parent set (inheritance)
        if ($this->parentSet) {
            $attributes = $attributes->merge($this->parentSet->getAllAttributes());
        }

        // Get attributes from this set's groups
        foreach ($this->groups as $group) {
            $attributes = $attributes->merge($group->attributes);
        }

        // Remove duplicates by attribute ID
        return $attributes->unique('id')->values();
    }

    /**
     * Scope active sets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope default sets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope by product type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $productTypeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProductType($query, int $productTypeId)
    {
        return $query->where('product_type_id', $productTypeId);
    }
}


