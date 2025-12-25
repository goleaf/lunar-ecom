<?php

namespace App\Models;

use Lunar\Models\Product as LunarProduct;

/**
 * Custom Product model extending Lunar's Product model.
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
 *     \Lunar\Models\Contracts\Product::class,
 *     \App\Models\Product::class,
 * );
 * 
 * Or use addDirectory() to register all models in a directory:
 * 
 * \Lunar\Facades\ModelManifest::addDirectory(__DIR__.'/../Models');
 * 
 * See: https://docs.lunarphp.com/1.x/extending/models
 */
class Product extends LunarProduct
{
    /**
     * Example custom relationship.
     * 
     * Add your own relationships here.
     */
    public function reviews()
    {
        // Example: Relationship to a reviews table
        // return $this->hasMany(Review::class);
    }

    /**
     * Example custom method.
     */
    public function getFullNameAttribute(): string
    {
        return $this->translateAttribute('name') . ' - Custom';
    }

    /**
     * Example custom scope.
     */
    public function scopeFeatured($query)
    {
        // Example: Filter featured products
        // return $query->where('featured', true);
        return $query;
    }

    /**
     * Example custom static method.
     * 
     * This can be called via \Lunar\Models\Product::someCustomMethod()
     * after registration (static call forwarding).
     */
    public static function someCustomMethod(): string
    {
        return 'Hello from custom Product model!';
    }
}
