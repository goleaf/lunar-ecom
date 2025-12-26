<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

/**
 * Model for tracking variant attribute combinations.
 */
class VariantAttributeCombination extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_attribute_combinations';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'combination',
        'combination_hash',
        'defining_attributes',
        'informational_attributes',
        'status',
        'is_partial',
        'template_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'combination' => 'array',
        'defining_attributes' => 'array',
        'informational_attributes' => 'array',
        'is_partial' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($combination) {
            if (empty($combination->combination_hash)) {
                $combination->combination_hash = $combination->generateHash();
            }
        });
    }

    /**
     * Generate hash for combination.
     *
     * @return string
     */
    public function generateHash(): string
    {
        $combination = $this->combination ?? [];
        ksort($combination);
        return hash('sha256', json_encode($combination));
    }

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
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Template relationship.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(VariantTemplate::class, 'template_id');
    }

    /**
     * Check if combination is complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return !$this->is_partial;
    }

    /**
     * Get combination as string.
     *
     * @return string
     */
    public function getCombinationString(): string
    {
        $parts = [];
        foreach ($this->combination ?? [] as $optionId => $valueId) {
            $option = \Lunar\Models\ProductOption::find($optionId);
            $value = \Lunar\Models\ProductOptionValue::find($valueId);
            if ($option && $value) {
                $parts[] = $option->translateAttribute('name') . ': ' . $value->translateAttribute('name');
            }
        }
        return implode(', ', $parts);
    }
}


