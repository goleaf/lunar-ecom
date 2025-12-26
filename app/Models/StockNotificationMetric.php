<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\ProductVariant;
use Lunar\Models\Order;

/**
 * StockNotificationMetric model for tracking notification performance.
 */
class StockNotificationMetric extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'stock_notification_metrics';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_notification_id',
        'product_variant_id',
        'email_sent',
        'email_sent_at',
        'email_delivered',
        'email_delivered_at',
        'email_opened',
        'email_opened_at',
        'email_open_count',
        'link_clicked',
        'link_clicked_at',
        'link_click_count',
        'clicked_link_type',
        'converted',
        'converted_at',
        'order_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'email_delivered' => 'boolean',
        'email_delivered_at' => 'datetime',
        'email_opened' => 'boolean',
        'email_opened_at' => 'datetime',
        'email_open_count' => 'integer',
        'link_clicked' => 'boolean',
        'link_clicked_at' => 'datetime',
        'link_click_count' => 'integer',
        'converted' => 'boolean',
        'converted_at' => 'datetime',
    ];

    /**
     * Stock notification relationship.
     *
     * @return BelongsTo
     */
    public function stockNotification(): BelongsTo
    {
        return $this->belongsTo(StockNotification::class);
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
     * Order relationship.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mark email as sent.
     *
     * @return void
     */
    public function markEmailSent(): void
    {
        $this->update([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }

    /**
     * Mark email as delivered.
     *
     * @return void
     */
    public function markEmailDelivered(): void
    {
        $this->update([
            'email_delivered' => true,
            'email_delivered_at' => now(),
        ]);
    }

    /**
     * Track email open.
     *
     * @return void
     */
    public function trackEmailOpen(): void
    {
        $this->increment('email_open_count');
        if (!$this->email_opened) {
            $this->update([
                'email_opened' => true,
                'email_opened_at' => now(),
            ]);
        }
    }

    /**
     * Track link click.
     *
     * @param  string  $linkType
     * @return void
     */
    public function trackLinkClick(string $linkType): void
    {
        $this->increment('link_click_count');
        if (!$this->link_clicked) {
            $this->update([
                'link_clicked' => true,
                'link_clicked_at' => now(),
                'clicked_link_type' => $linkType,
            ]);
        }
    }

    /**
     * Mark as converted (purchased).
     *
     * @param  int|null  $orderId
     * @return void
     */
    public function markAsConverted(?int $orderId = null): void
    {
        $this->update([
            'converted' => true,
            'converted_at' => now(),
            'order_id' => $orderId,
        ]);
    }
}


