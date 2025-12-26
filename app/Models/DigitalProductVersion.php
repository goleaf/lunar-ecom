<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * DigitalProductVersion model for managing file versions and updates.
 */
class DigitalProductVersion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'digital_product_versions';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'digital_product_id',
        'version',
        'file_path',
        'file_size',
        'mime_type',
        'original_filename',
        'release_notes',
        'is_current',
        'notify_customers',
        'released_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'is_current' => 'boolean',
        'notify_customers' => 'boolean',
        'released_at' => 'datetime',
    ];

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
}


