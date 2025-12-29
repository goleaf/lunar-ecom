<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;

class ProductAvailability extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_availability';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'availability_type',
        'start_date',
        'end_date',
        'available_dates',
        'unavailable_dates',
        'is_recurring',
        'recurrence_pattern',
        'max_quantity_per_date',
        'total_quantity',
        'available_from',
        'available_until',
        'slot_duration_minutes',
        'is_active',
        'timezone',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'available_dates' => 'array',
        'unavailable_dates' => 'array',
        'is_recurring' => 'boolean',
        'recurrence_pattern' => 'array',
        'max_quantity_per_date' => 'integer',
        'total_quantity' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'slot_duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get bookings for this availability.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(AvailabilityBooking::class, 'product_id', 'product_id')
            ->where(function ($q) {
                $q->whereNull('product_variant_id')
                  ->orWhereColumn('product_variant_id', 'product_availability.product_variant_id');
            });
    }

    /**
     * Scope to get active availability.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get availability for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where(function ($subQ) use ($startDate, $endDate) {
                // Date range availability
                $subQ->where('availability_type', 'date_range')
                    ->where('start_date', '<=', $endDate)
                    ->where(function ($dateQ) use ($startDate) {
                        $dateQ->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    });
            })
            ->orWhere(function ($subQ) use ($startDate, $endDate) {
                // Specific dates
                $subQ->where('availability_type', 'specific_dates')
                    ->whereJsonContains('available_dates', $startDate->toDateString());
            })
            ->orWhere('availability_type', 'always_available')
            ->orWhere(function ($subQ) {
                // Recurring
                $subQ->where('availability_type', 'recurring')
                    ->where('is_recurring', true);
            });
        });
    }

    /**
     * Check if date is available based on this rule.
     */
    public function isDateAvailable(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check blackout dates
        if ($this->unavailable_dates && in_array($date->toDateString(), $this->unavailable_dates)) {
            return false;
        }

        switch ($this->availability_type) {
            case 'always_available':
                return true;

            case 'date_range':
                if ($this->start_date && $date->lt($this->start_date)) {
                    return false;
                }
                if ($this->end_date && $date->gt($this->end_date)) {
                    return false;
                }
                return true;

            case 'specific_dates':
                return $this->available_dates && in_array($date->toDateString(), $this->available_dates);

            case 'recurring':
                return $this->matchesRecurrencePattern($date);

            default:
                return false;
        }
    }

    /**
     * Check if date matches recurrence pattern.
     */
    protected function matchesRecurrencePattern(Carbon $date): bool
    {
        if (!$this->is_recurring || !$this->recurrence_pattern) {
            return false;
        }

        $pattern = $this->recurrence_pattern;
        $type = $pattern['type'] ?? null;

        switch ($type) {
            case 'weekly':
                $days = $pattern['days'] ?? [];
                $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
                return in_array($dayOfWeek, $days);

            case 'daily':
                return true;

            case 'monthly':
                $dayOfMonth = $pattern['day_of_month'] ?? null;
                return $dayOfMonth === null || $date->day === $dayOfMonth;

            default:
                return false;
        }
    }
}


