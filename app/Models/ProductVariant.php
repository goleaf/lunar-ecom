<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lunar\Models\ProductVariant as LunarProductVariant;

/**
 * Custom ProductVariant model extending Lunar's ProductVariant model.
 * 
 * This is an example of how to extend Lunar models. You can:
 * - Add custom relationships
 * - Add custom methods
 * - Add custom attributes/accessors
 * - Add scopes
 * 
 * To use this custom model, register it in AppServiceProvider::boot():
 * 
 * \Lunar\Facades\ModelManifest::replace(
 *     \Lunar\Models\Contracts\ProductVariant::class,
 *     \App\Models\ProductVariant::class,
 * );
 * 
 * Or use addDirectory() to register all models in a directory:
 * 
 * \Lunar\Facades\ModelManifest::addDirectory(__DIR__.'/../Models');
 * 
 * See: https://docs.lunarphp.com/1.x/extending/models
 */
class ProductVariant extends LunarProductVariant
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;
    /**
     * Example custom method.
     * 
     * This can be called via \Lunar\Models\ProductVariant::someCustomMethod()
     * after registration (static call forwarding).
     */
    public function someCustomMethod(): string
    {
        return 'Hello from custom ProductVariant model!';
    }

    /**
     * Example custom relationship.
     */
    public function inventoryMovements()
    {
        // Example: Relationship to inventory movements
        // return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Example custom accessor.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock > 10) {
            return 'in_stock';
        } elseif ($this->stock > 0) {
            return 'low_stock';
        }
        return 'out_of_stock';
    }
}
