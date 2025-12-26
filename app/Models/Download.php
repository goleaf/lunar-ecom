<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Lunar\Models\Customer;
use Lunar\Models\Order;

/**
 * Download model for tracking customer downloads of digital products.
 */
class Download extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'downloads';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'order_id',
        'digital_product_id',
        'download_token',
        'downloads_count',
        'expires_at',
        'first_downloaded_at',
        'last_downloaded_at',
        'ip_address',
        'user_agent',
        'license_key',
        'license_key_sent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'downloads_count' => 'integer',
        'expires_at' => 'datetime',
        'first_downloaded_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'license_key_sent' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($download) {
            if (empty($download->download_token)) {
                $download->download_token = Str::random(64);
            }
        });
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
     * Order relationship.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Digital product relationship.
     *
     * @return BelongsTo
     */
    public function digitalProduct(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class);
    }

    /**
     * Download logs relationship.
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    /**
     * Check if download is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if download limit is reached.
     *
     * @return bool
     */
    public function isLimitReached(): bool
    {
        $digitalProduct = $this->digitalProduct;
        if (!$digitalProduct) {
            return true;
        }

        return $digitalProduct->isDownloadLimitReached($this->downloads_count);
    }

    /**
     * Check if download is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->isLimitReached()) {
            return false;
        }

        return true;
    }

    /**
     * Increment download count.
     *
     * @return void
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('downloads_count');
        
        if (!$this->first_downloaded_at) {
            $this->first_downloaded_at = now();
        }
        
        $this->last_downloaded_at = now();
        $this->save();
    }

    /**
     * Get download URL.
     *
     * @return string
     */
    public function getDownloadUrl(): string
    {
        return route('frontend.downloads.download', ['token' => $this->download_token]);
    }
}



