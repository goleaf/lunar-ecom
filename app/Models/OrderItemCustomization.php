<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\OrderLine;

class OrderItemCustomization extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'order_item_customizations';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_item_id',
        'customization_id',
        'value',
        'value_type',
        'image_path',
        'image_original_name',
        'image_width',
        'image_height',
        'image_size_kb',
        'additional_cost',
        'currency_code',
        'production_notes',
        'preview_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'image_width' => 'integer',
        'image_height' => 'integer',
        'image_size_kb' => 'integer',
        'additional_cost' => 'decimal:2',
        'preview_data' => 'array',
    ];

    /**
     * Get the order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'order_item_id');
    }

    /**
     * Get the customization definition.
     */
    public function customization(): BelongsTo
    {
        return $this->belongsTo(ProductCustomization::class, 'customization_id');
    }

    /**
     * Get the image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return \Storage::url($this->image_path);
    }
}


