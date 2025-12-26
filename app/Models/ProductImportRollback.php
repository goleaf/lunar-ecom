<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

/**
 * ProductImportRollback model for tracking rollback operations.
 */
class ProductImportRollback extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_import_rollbacks';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_import_id',
        'product_id',
        'original_data',
        'action',
        'rolled_back_by',
        'rolled_back_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'original_data' => 'array',
        'rolled_back_at' => 'datetime',
    ];

    /**
     * Product import relationship.
     *
     * @return BelongsTo
     */
    public function productImport(): BelongsTo
    {
        return $this->belongsTo(ProductImport::class);
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
     * User who rolled back.
     *
     * @return BelongsTo
     */
    public function rolledBackBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'rolled_back_by');
    }
}


