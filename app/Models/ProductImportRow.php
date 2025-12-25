<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

/**
 * ProductImportRow model for tracking individual import rows.
 */
class ProductImportRow extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_import_rows';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_import_id',
        'row_number',
        'status',
        'raw_data',
        'mapped_data',
        'validation_errors',
        'product_id',
        'sku',
        'error_message',
        'success_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'raw_data' => 'array',
        'mapped_data' => 'array',
        'validation_errors' => 'array',
        'row_number' => 'integer',
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
}

