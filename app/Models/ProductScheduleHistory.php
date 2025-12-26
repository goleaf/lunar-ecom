<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

/**
 * ProductScheduleHistory model for tracking scheduling history and audit log.
 */
class ProductScheduleHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_schedule_history';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_schedule_id',
        'product_id',
        'action',
        'previous_status',
        'new_status',
        'metadata',
        'executed_by',
        'executed_at',
        'timezone',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'executed_at' => 'datetime',
    ];

    /**
     * Product schedule relationship.
     *
     * @return BelongsTo
     */
    public function productSchedule(): BelongsTo
    {
        return $this->belongsTo(ProductSchedule::class);
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
     * User who executed the action.
     *
     * @return BelongsTo
     */
    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'executed_by');
    }
}


