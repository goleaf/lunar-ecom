<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * DigitalProduct model for managing digital product files and settings.
 */
class DigitalProduct extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'digital_products';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'is_digital',
        'file_path',
        'file_size',
        'file_type',
        'file_name',
        'download_limit',
        'download_expiry_days',
        'require_login',
        'storage_disk',
        'auto_deliver',
        'send_email',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'download_limit' => 'integer',
        'download_expiry_days' => 'integer',
        'is_digital' => 'boolean',
        'require_login' => 'boolean',
        'auto_deliver' => 'boolean',
        'send_email' => 'boolean',
    ];

    /**
     * Product variant relationship.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Downloads relationship.
     *
     * @return HasMany
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    /**
     * Versions relationship.
     *
     * @return HasMany
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DigitalProductVersion::class);
    }

    /**
     * Current version relationship.
     *
     * @return HasOne
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(DigitalProductVersion::class)->where('is_current', true);
    }

    /**
     * Get the decrypted file path.
     *
     * @return string|null
     */
    public function getDecryptedFilePath(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        try {
            return decrypt($this->file_path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if file exists in storage.
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        $path = $this->getDecryptedFilePath();
        if (!$path) {
            return false;
        }

        return Storage::disk($this->storage_disk ?: 'private')->exists($path);
    }

    /**
     * Get file URL (for download).
     *
     * @return string|null
     */
    public function getFileUrl(): ?string
    {
        $path = $this->getDecryptedFilePath();
        if (!$path || !$this->fileExists()) {
            return null;
        }

        $diskName = $this->storage_disk ?: 'private';
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);

        return $disk->url($path);
    }

    /**
     * Get formatted file size.
     *
     * @return string
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if download limit is reached.
     *
     * @param  int  $downloadCount
     * @return bool
     */
    public function isDownloadLimitReached(int $downloadCount): bool
    {
        if ($this->download_limit === null) {
            return false; // Unlimited
        }

        return $downloadCount >= $this->download_limit;
    }

    /**
     * Check if license key is required.
     *
     * @return bool
     */
    public function requiresLicense(): bool
    {
        // License key rules are stored on the Download model currently.
        return false;
    }

    /**
     * Convenience accessor for the owning product (via the variant).
     */
    public function getProductAttribute(): ?Product
    {
        return $this->productVariant?->product;
    }

    /**
     * Backwards-compatible alias for `file_type`.
     */
    public function getMimeTypeAttribute(): ?string
    {
        return $this->file_type;
    }

    /**
     * Backwards-compatible alias for `file_name`.
     */
    public function getOriginalFilenameAttribute(): ?string
    {
        return $this->file_name;
    }

    /**
     * Backwards-compatible alias for `product_id` (resolved via variant).
     */
    public function getProductIdAttribute(): ?int
    {
        return $this->productVariant?->product_id;
    }

    /**
     * Backwards-compatible alias for "active" flag (record existence implies active).
     */
    public function getIsActiveAttribute(): bool
    {
        return true;
    }

    /**
     * Backwards-compatible version accessor (from current version when available).
     */
    public function getVersionAttribute(): ?string
    {
        return $this->currentVersion?->version;
    }

    /**
     * Backwards-compatible release notes accessor (from current version when available).
     */
    public function getReleaseNotesAttribute(): ?string
    {
        return $this->currentVersion?->release_notes;
    }
}
