<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Lunar\Models\Product;

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
        'product_id',
        'file_path',
        'file_size',
        'mime_type',
        'original_filename',
        'download_limit',
        'download_expiry_days',
        'license_key_pattern',
        'version',
        'release_notes',
        'requires_license_key',
        'is_active',
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
        'requires_license_key' => 'boolean',
        'is_active' => 'boolean',
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
     * @return HasMany
     */
    public function currentVersion()
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

        return Storage::disk('private')->exists($path);
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

        return Storage::disk('private')->url($path);
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
        return $this->requires_license_key && !empty($this->license_key_pattern);
    }
}
