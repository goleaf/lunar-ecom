<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\Customer;

/**
 * StockNotification model for tracking product availability notifications.
 */
class StockNotification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'stock_notifications';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'customer_id',
        'email',
        'name',
        'phone',
        'status',
        'notified_at',
        'notification_count',
        'notify_on_backorder',
        'min_quantity',
        'token',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'notified_at' => 'datetime',
        'notification_count' => 'integer',
        'notify_on_backorder' => 'boolean',
        'min_quantity' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->token)) {
                $notification->token = Str::random(32);
            }
        });
    }

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
     * Product variant relationship.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Customer relationship.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the variant to check stock for.
     *
     * @return ProductVariant|null
     */
    public function getVariant(): ?ProductVariant
    {
        if ($this->product_variant_id) {
            return $this->productVariant;
        }

        return $this->product->variants->first();
    }

    /**
     * Check if notification should be sent.
     *
     * @return bool
     */
    public function shouldNotify(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $variant = $this->getVariant();
        if (!$variant) {
            return false;
        }

        // Check if product is in stock
        $inStock = $variant->stock > 0;

        // Check minimum quantity requirement
        if ($this->min_quantity && $variant->stock < $this->min_quantity) {
            return false;
        }

        // Check backorder preference
        if (!$inStock && !$this->notify_on_backorder && !$variant->backorder) {
            return false;
        }

        return $inStock || ($this->notify_on_backorder && $variant->backorder);
    }

    /**
     * Mark as notified.
     *
     * @return void
     */
    public function markAsNotified(): void
    {
        $this->update([
            'status' => 'sent',
            'notified_at' => now(),
            'notification_count' => $this->notification_count + 1,
        ]);
    }

    /**
     * Cancel notification.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Scope to get pending notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get sent notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get notifications for a product.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get notifications for a variant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $variantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }
}
