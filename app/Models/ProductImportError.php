<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductImportError model for tracking import errors.
 */
class ProductImportError extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_import_errors';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'import_id',
        'row_number',
        'field',
        'error_message',
        'error_type',
        'row_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'row_number' => 'integer',
        'row_data' => 'array',
    ];

    /**
     * Import relationship.
     *
     * @return BelongsTo
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(ProductImport::class, 'import_id');
    }
}

