<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use App\Models\User;

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
        'priority',
        'is_hidden_from_frontend',
        'countdown_message',
        'expected_available_at',
        'auto_archive_on_expiry',
        'auto_unpublish_on_expiry',
        'created_by',
        'updated_by',
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
        'time_start' => 'datetime', // Stored as TIME in DB, but cast to datetime for easier comparison
        'time_end' => 'datetime', // Stored as TIME in DB, but cast to datetime for easier comparison
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
        'priority' => 'integer',
        'is_hidden_from_frontend' => 'boolean',
        'expected_available_at' => 'datetime',
        'auto_archive_on_expiry' => 'boolean',
        'auto_unpublish_on_expiry' => 'boolean',
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
     * Creator relationship.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater relationship.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * History relationship.
     */
    public function history(): HasMany
    {
        return $this->hasMany(ProductScheduleHistory::class);
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

        // For one-time schedules
        if ($this->schedule_type === 'one_time') {
            return $this->scheduled_at->isPast() &&
                   (!$this->expires_at || $this->expires_at->isFuture()) &&
                   !$this->executed_at; // Ensure it hasn't been executed yet
        }

        // For recurring schedules, check if it's currently active within its recurrence pattern
        if ($this->schedule_type === 'recurring') {
            return $this->isCurrentlyActive();
        }

        return false;
    }

    /**
     * Check if a recurring schedule is currently active based on its pattern.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active || !$this->is_recurring) {
            return false;
        }

        $now = now()->setTimezone($this->timezone ?? config('app.timezone', 'UTC'));

        // Check date range for the overall schedule
        if ($this->scheduled_at->isFuture() || ($this->expires_at && $this->expires_at->isPast())) {
            return false;
        }

        // Check recurrence pattern
        switch ($this->recurrence_pattern) {
            case 'daily':
                // Always active within the date range
                break;
            case 'weekly':
                if (!in_array($now->dayOfWeek, $this->days_of_week ?? [])) {
                    return false;
                }
                break;
            case 'monthly':
                // Assuming recurrence_config might hold specific days of month or 'first monday' etc.
                // For simplicity, let's assume it's active all month if no specific day is set
                // Or if recurrence_config specifies a day, check that.
                // For now, just check if it's within the month range if no specific day is configured.
                // More complex logic would be needed here based on actual config.
                break;
            case 'yearly':
                if ($now->month !== $this->scheduled_at->month || $now->day !== $this->scheduled_at->day) {
                    return false;
                }
                break;
            default:
                return false;
        }

        // Check time range if specified
        if ($this->time_start && $this->time_end) {
            // Parse time strings (TIME columns return as 'H:i:s' strings)
            $timeStart = is_string($this->time_start) ? $this->time_start : $this->time_start->format('H:i:s');
            $timeEnd = is_string($this->time_end) ? $this->time_end : $this->time_end->format('H:i:s');

            $startTime = $now->copy()->setTimeFromTimeString($timeStart);
            $endTime = $now->copy()->setTimeFromTimeString($timeEnd);

            // If end time is before start time, it spans midnight
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
                return $now->between($startTime, $endTime) || $now->copy()->addDay()->between($startTime, $endTime);
            }

            return $now->between($startTime, $endTime);
        }

        return true;
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
     * Check if product is coming soon.
     */
    public function isComingSoon(): bool
    {
        return $this->type === 'coming_soon' && $this->expected_available_at && $this->expected_available_at->isFuture();
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

    /**
     * Scope to get flash sales.
     */
    public function scopeFlashSales($query)
    {
        return $query->where('type', 'flash_sale');
    }

    /**
     * Scope to get seasonal schedules.
     */
    public function scopeSeasonal($query)
    {
        return $query->where('type', 'seasonal');
    }

    /**
     * Scope to get time-limited schedules.
     */
    public function scopeTimeLimited($query)
    {
        return $query->where('type', 'time_limited');
    }

    /**
     * Scope to get schedules that are currently active based on their recurrence pattern.
     */
    public function scopeActiveNow($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->where(function ($subQ) use ($now) { // One-time schedules that are due
                    $subQ->where('schedule_type', 'one_time')
                         ->where('scheduled_at', '<=', $now)
                         ->where(function ($subSubQ) use ($now) {
                             $subSubQ->whereNull('expires_at')
                                     ->orWhere('expires_at', '>', $now);
                         })
                         ->whereNull('executed_at');
                })->orWhere(function ($subQ) use ($now) { // Recurring schedules that are currently active
                    $subQ->where('schedule_type', 'recurring')
                         ->where('scheduled_at', '<=', $now) // Overall schedule start
                         ->where(function ($subSubQ) use ($now) {
                             $subSubQ->whereNull('expires_at')
                                     ->orWhere('expires_at', '>', $now); // Overall schedule end
                         })
                         ->where('is_recurring', true);
                });
            });
    }
}

