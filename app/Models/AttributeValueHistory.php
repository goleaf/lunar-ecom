<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Attribute Value History model.
 * 
 * Tracks all changes to attribute values:
 * - Created, updated, deleted
 * - Before/after values
 * - Who made the change
 */
class AttributeValueHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'attribute_value_history';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'valueable_type',
        'valueable_id',
        'attribute_id',
        'value_before',
        'value_after',
        'numeric_value_before',
        'numeric_value_after',
        'text_value_before',
        'text_value_after',
        'change_type',
        'locale',
        'changed_by',
        'change_reason',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value_before' => 'array',
        'value_after' => 'array',
        'numeric_value_before' => 'decimal:4',
        'numeric_value_after' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Polymorphic relationship to value (product/variant/channel).
     *
     * @return MorphTo
     */
    public function valueable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Attribute relationship.
     *
     * @return BelongsTo
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * User who made the change.
     *
     * @return BelongsTo
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }

    /**
     * Scope by change type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChangeType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope by locale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $locale
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}


