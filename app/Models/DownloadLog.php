<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DownloadLog model for tracking individual download events.
 */
class DownloadLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'download_logs';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'download_id',
        'digital_product_id',
        'ip_address',
        'user_agent',
        'version',
        'bytes_downloaded',
        'completed',
        'downloaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bytes_downloaded' => 'integer',
        'completed' => 'boolean',
        'downloaded_at' => 'datetime',
    ];

    /**
     * Download relationship.
     *
     * @return BelongsTo
     */
    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class);
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
}
