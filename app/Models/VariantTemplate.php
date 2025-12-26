<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for variant templates/presets.
 */
class VariantTemplate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_templates';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'product_type_id',
        'default_combination',
        'default_fields',
        'attribute_config',
        'usage_count',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'default_combination' => 'array',
        'default_fields' => 'array',
        'attribute_config' => 'array',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
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
     * Variant combinations using this template.
     *
     * @return HasMany
     */
    public function combinations(): HasMany
    {
        return $this->hasMany(VariantAttributeCombination::class, 'template_id');
    }

    /**
     * Increment usage count.
     *
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}


