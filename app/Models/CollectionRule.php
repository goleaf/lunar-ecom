<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CollectionRule model for rule-based (dynamic) collections.
 * 
 * Allows collections to automatically include products based on conditions.
 */
class CollectionRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'collection_rules';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_id',
        'conditions',
        'logic',
        'priority',
        'is_active',
        'product_limit',
        'sort_by',
        'sort_direction',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'product_limit' => 'integer',
    ];

    /**
     * Collection relationship.
     *
     * @return BelongsTo
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
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
     * Scope to order by priority.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}

