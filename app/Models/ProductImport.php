<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProductImport model for tracking import operations.
 */
class ProductImport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_imports';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'file_path',
        'file_name',
        'original_filename',
        'file_size',
        'file_type',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'skipped_rows',
        'field_mapping',
        'options',
        'validation_errors',
        'import_report',
        'error_summary',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'field_mapping' => 'array',
        'options' => 'array',
        'validation_errors' => 'array',
        'import_report' => 'array',
        'error_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'skipped_rows' => 'integer',
    ];

    /**
     * Backwards-compatible alias for legacy code.
     */
    public function getFilenameAttribute(): ?string
    {
        return $this->file_name;
    }

    /**
     * Backwards-compatible alias for legacy code.
     */
    public function setFilenameAttribute(?string $value): void
    {
        $this->attributes['file_name'] = $value;
    }

    /**
     * Backwards-compatible alias for legacy code.
     */
    public function getImportOptionsAttribute(): array
    {
        return $this->options ?? [];
    }

    /**
     * Backwards-compatible alias for legacy code.
     */
    public function setImportOptionsAttribute($value): void
    {
        $this->attributes['options'] = $value;
    }

    /**
     * User relationship.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Import rows relationship.
     *
     * @return HasMany
     */
    public function rows(): HasMany
    {
        return $this->hasMany(ProductImportRow::class);
    }

    /**
     * Rollbacks relationship.
     *
     * @return HasMany
     */
    public function rollbacks(): HasMany
    {
        return $this->hasMany(ProductImportRollback::class);
    }

    /**
     * Get progress percentage.
     *
     * @return float
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    /**
     * Check if import is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    /**
     * Check if import can be rolled back.
     *
     * @return bool
     */
    public function canRollback(): bool
    {
        return $this->status === 'completed' && $this->successful_rows > 0;
    }
}
