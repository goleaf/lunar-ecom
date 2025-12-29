<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;
use App\Models\ProductVariant;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\Customer;
use Carbon\Carbon;

class AvailabilityBooking extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'availability_bookings';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'order_id',
        'order_line_id',
        'customer_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'quantity',
        'status',
        'total_price',
        'currency_code',
        'duration_days',
        'pricing_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'admin_notes',
        'cancelled_at',
        'cancellation_reason',
        'timezone',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'duration_days' => 'integer',
        'cancelled_at' => 'datetime',
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
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the order line.
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'order_line_id');
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope to get confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get bookings for a date.
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Scope to get active bookings.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get booking duration in days.
     */
    public function getDurationDays(): int
    {
        if ($this->duration_days) {
            return $this->duration_days;
        }

        if ($this->end_date && $this->start_date) {
            return $this->start_date->diffInDays($this->end_date) + 1;
        }

        return 1;
    }
}


