<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BundleAnalytic model for tracking bundle performance.
 */
class BundleAnalytic extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'bundle_analytics';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bundle_id',
        'order_id',
        'event_type',
        'user_id',
        'session_id',
        'selected_items',
        'bundle_price',
        'original_price',
        'savings_amount',
        'savings_percentage',
        'event_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'selected_items' => 'array',
        'bundle_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'savings_amount' => 'decimal:2',
        'savings_percentage' => 'decimal:2',
        'event_at' => 'datetime',
    ];

    /**
     * Bundle relationship.
     *
     * @return BelongsTo
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * Order relationship.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Order::class);
    }

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
     * Scope to filter by event type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}


