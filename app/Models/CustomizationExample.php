<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

class CustomizationExample extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'customization_examples';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'customization_id',
        'title',
        'description',
        'example_image',
        'customization_values',
        'display_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'customization_values' => 'array',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the customization.
     */
    public function customization(): BelongsTo
    {
        return $this->belongsTo(ProductCustomization::class, 'customization_id');
    }

    /**
     * Scope to get active examples.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}


