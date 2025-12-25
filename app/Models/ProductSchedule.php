<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

/**
 * ProductSchedule model for scheduling product publish/unpublish.
 */
class ProductSchedule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_schedules';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'type',
        'schedule_type',
        'scheduled_at',
        'expires_at',
        'start_date',
        'end_date',
        'days_of_week',
        'time_start',
        'time_end',
        'timezone',
        'target_status',
        'is_active',
        'sale_price',
        'sale_percentage',
        'restore_original_price',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_config',
        'send_notification',
        'notification_sent_at',
        'notification_hours_before',
        'notification_scheduled_at',
        'executed_at',
        'execution_success',
        'execution_error',
        'season_tag',
        'auto_unpublish_after_season',
        'is_coming_soon',
        'coming_soon_message',
        'bulk_schedule_id',
        'applied_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'time_start' => 'datetime',
        'time_end' => 'datetime',
        'days_of_week' => 'array',
        'is_active' => 'boolean',
        'sale_price' => 'decimal:2',
        'sale_percentage' => 'integer',
        'restore_original_price' => 'boolean',
        'is_recurring' => 'boolean',
        'recurrence_config' => 'array',
        'send_notification' => 'boolean',
        'notification_sent_at' => 'datetime',
        'notification_scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'execution_success' => 'boolean',
        'auto_unpublish_after_season' => 'boolean',
        'is_coming_soon' => 'boolean',
        'applied_to' => 'array',
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
     * Check if schedule is due.
     *
     * @return bool
     */
    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->scheduled_at->isPast() && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if schedule is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if schedule is for flash sale.
     *
     * @return bool
     */
    public function isFlashSale(): bool
    {
        return $this->type === 'flash_sale';
    }

    /**
     * Check if schedule is time-limited.
     *
     * @return bool
     */
    public function isTimeLimited(): bool
    {
        return $this->type === 'time_limited' && $this->expires_at;
    }

    /**
     * Scope to get active schedules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get due schedules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDue($query)
    {
        return $query->where('scheduled_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where('is_active', true)
            ->whereNull('executed_at');
    }

    /**
     * Scope to get expired schedules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('is_active', true);
    }

    /**
     * Scope to get upcoming schedules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->where('is_active', true);
    }
}

