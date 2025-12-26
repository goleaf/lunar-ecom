<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product;

class ProductCustomization extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_customizations';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'customization_type',
        'field_name',
        'field_label',
        'description',
        'placeholder',
        'is_required',
        'min_length',
        'max_length',
        'pattern',
        'allowed_values',
        'allowed_formats',
        'max_file_size_kb',
        'min_width',
        'max_width',
        'min_height',
        'max_height',
        'aspect_ratio_width',
        'aspect_ratio_height',
        'price_modifier',
        'price_modifier_type',
        'display_order',
        'is_active',
        'show_in_preview',
        'preview_settings',
        'template_image',
        'example_values',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_required' => 'boolean',
        'min_length' => 'integer',
        'max_length' => 'integer',
        'allowed_values' => 'array',
        'allowed_formats' => 'array',
        'max_file_size_kb' => 'integer',
        'min_width' => 'integer',
        'max_width' => 'integer',
        'min_height' => 'integer',
        'max_height' => 'integer',
        'aspect_ratio_width' => 'integer',
        'aspect_ratio_height' => 'integer',
        'price_modifier' => 'decimal:2',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'show_in_preview' => 'boolean',
        'preview_settings' => 'array',
        'example_values' => 'array',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get order item customizations using this customization.
     */
    public function orderItemCustomizations(): HasMany
    {
        return $this->hasMany(OrderItemCustomization::class, 'customization_id');
    }

    /**
     * Get examples for this customization.
     */
    public function examples(): HasMany
    {
        return $this->hasMany(CustomizationExample::class, 'customization_id')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Scope to get active customizations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get customizations for preview.
     */
    public function scopeForPreview($query)
    {
        return $query->where('is_active', true)
            ->where('show_in_preview', true);
    }

    /**
     * Check if customization is for text.
     */
    public function isTextType(): bool
    {
        return $this->customization_type === 'text';
    }

    /**
     * Check if customization is for image.
     */
    public function isImageType(): bool
    {
        return $this->customization_type === 'image';
    }

    /**
     * Check if customization is for option.
     */
    public function isOptionType(): bool
    {
        return $this->customization_type === 'option';
    }
}


