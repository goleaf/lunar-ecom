<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductView model for tracking product views.
 */
class ProductView extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_views';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
        'viewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'viewed_at' => 'datetime',
    ];

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
     * User relationship.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope to get recent views.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('viewed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get views for a user or session.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUserOrSession($query, ?int $userId = null, ?string $sessionId = null)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        
        if ($sessionId) {
            return $query->where('session_id', $sessionId);
        }
        
        return $query;
    }
}
