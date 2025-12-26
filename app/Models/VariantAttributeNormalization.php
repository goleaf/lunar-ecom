<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for attribute value normalization.
 */
class VariantAttributeNormalization extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_attribute_normalizations';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'option_id',
        'source_value',
        'normalized_value_id',
        'type',
        'case_sensitive',
        'priority',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'case_sensitive' => 'boolean',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Option relationship.
     *
     * @return BelongsTo
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductOption::class, 'option_id');
    }

    /**
     * Normalized value relationship.
     *
     * @return BelongsTo
     */
    public function normalizedValue(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductOptionValue::class, 'normalized_value_id');
    }

    /**
     * Normalize a value.
     *
     * @param  int  $optionId
     * @param  string  $value
     * @return int|null  Normalized value ID
     */
    public static function normalize(int $optionId, string $value): ?int
    {
        $normalization = static::where('option_id', $optionId)
            ->where('is_active', true)
            ->where(function ($q) use ($value) {
                $q->where('source_value', $value)
                  ->orWhere(function ($subQ) use ($value) {
                      $subQ->where('case_sensitive', false)
                           ->whereRaw('LOWER(source_value) = LOWER(?)', [$value]);
                  });
            })
            ->orderByDesc('priority')
            ->first();

        return $normalization?->normalized_value_id;
    }
}


