<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Attribute Group model.
 * 
 * Represents a reusable group of attributes.
 * Groups can be shared across multiple attribute sets.
 */
class AttributeGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'attribute_groups';
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
        'is_reusable',
        'is_active',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_reusable' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Attribute sets relationship.
     *
     * @return BelongsToMany
     */
    public function attributeSets(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeSet::class,
            config('lunar.database.table_prefix') . 'attribute_set_groups',
            'attribute_group_id',
            'attribute_set_id'
        )
        ->withPivot(['position', 'visibility_conditions', 'is_visible', 'is_collapsible', 'is_collapsed_by_default'])
        ->withTimestamps()
        ->orderBy('position');
    }

    /**
     * Attributes relationship.
     *
     * @return BelongsToMany
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Attribute::class,
            config('lunar.database.table_prefix') . 'attribute_group_attributes',
            'attribute_group_id',
            'attribute_id'
        )
        ->withPivot(['position', 'visibility_conditions', 'is_visible', 'is_required', 'label_override', 'help_text', 'display_config'])
        ->withTimestamps()
        ->orderBy('position');
    }

    /**
     * Scope reusable groups.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReusable($query)
    {
        return $query->where('is_reusable', true);
    }

    /**
     * Scope active groups.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}


