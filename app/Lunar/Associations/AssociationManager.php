<?php

namespace App\Lunar\Associations;

use Illuminate\Support\Collection;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;
use Lunar\Base\Enums\Concerns\ProvidesProductAssociationType;
use Lunar\Models\Product;
use Lunar\Models\ProductAssociation;

/**
 * Helper class for managing product associations.
 * 
 * Provides synchronous methods for creating associations (useful in seeders, commands, etc.)
 * 
 * Usage:
 * ```php
 * $manager = new AssociationManager();
 * $manager->associate($product, $targetProduct, ProductAssociationEnum::CROSS_SELL);
 * $manager->associateMultiple($product, [$product1, $product2], ProductAssociationEnum::UP_SELL);
 * $manager->dissociate($product, $targetProduct, ProductAssociationEnum::CROSS_SELL);
 * ```
 */
class AssociationManager
{
    /**
     * Associate a product with another product (synchronous).
     * 
     * @param Product $product The parent product
     * @param Product|Collection|array $targets The target product(s) to associate
     * @param ProvidesProductAssociationType|string $type The association type
     */
    public function associate(
        Product $product,
        Product|Collection|array $targets,
        ProvidesProductAssociationType|string $type
    ): void {
        if (is_array($targets)) {
            $targets = collect($targets);
        }

        if (!($targets instanceof Collection)) {
            $targets = collect([$targets]);
        }

        $typeValue = is_string($type) ? $type : $type->value;

        $product->associations()->createMany(
            $targets->map(function ($target) use ($typeValue) {
                return [
                    'product_target_id' => $target->id,
                    'type' => $typeValue,
                ];
            })
        );
    }

    /**
     * Dissociate a product from another product.
     * 
     * @param Product $product The parent product
     * @param Product|Collection|array $targets The target product(s) to dissociate
     * @param ProvidesProductAssociationType|string|null $type The association type (null to remove all types)
     */
    public function dissociate(
        Product $product,
        Product|Collection|array $targets,
        ProvidesProductAssociationType|string|null $type = null
    ): void {
        if (is_array($targets)) {
            $targets = collect($targets);
        }

        if (!($targets instanceof Collection)) {
            $targets = collect([$targets]);
        }

        $query = $product->associations()
            ->whereIn('product_target_id', $targets->pluck('id'));

        if ($type !== null) {
            $typeValue = is_string($type) ? $type : $type->value;
            $query->where('type', $typeValue);
        }

        $query->delete();
    }
}


