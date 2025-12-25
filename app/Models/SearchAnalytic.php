<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SearchAnalytic model for tracking search queries and results.
 */
class SearchAnalytic extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'search_analytics';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'search_term',
        'result_count',
        'zero_results',
        'clicked_product_id',
        'ip_address',
        'user_agent',
        'user_id',
        'filters',
        'session_id',
        'searched_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'result_count' => 'integer',
        'zero_results' => 'boolean',
        'filters' => 'array',
        'searched_at' => 'datetime',
    ];

    /**
     * User relationship.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Product relationship (for clicked products).
     *
     * @return BelongsTo
     */
    public function clickedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'clicked_product_id');
    }
}

