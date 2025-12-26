<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;
use Lunar\Models\Customer;

class AvailabilityNotification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'availability_notifications';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'booking_id',
        'customer_id',
        'notification_type',
        'message',
        'metadata',
        'is_sent',
        'sent_at',
        'email',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(AvailabilityBooking::class, 'booking_id');
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope to get unsent notifications.
     */
    public function scopeUnsent($query)
    {
        return $query->where('is_sent', false);
    }
}


