<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Lunar\Associations\AssociationManager;
use Illuminate\Http\Request;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;
use Lunar\Models\Product;

/**
 * Controller for managing product associations via API/admin interface.
 * 
 * This demonstrates how to programmatically manage associations.
 * See: https://docs.lunarphp.com/1.x/reference/associations
 */
class ProductAssociationController extends Controller
{
    public function __construct(
        protected AssociationManager $associationManager
    ) {}

    /**
     * Associate products (example API endpoint).
     * 
     * POST /api/products/{product}/associations
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'target_product_id' => 'required|exists:lunar_products,id',
            'type' => 'required|in:cross-sell,up-sell,alternate',
        ]);

        $targetProduct = Product::findOrFail($request->target_product_id);
        
        $type = match($request->type) {
            'cross-sell' => ProductAssociationEnum::CROSS_SELL,
            'up-sell' => ProductAssociationEnum::UP_SELL,
            'alternate' => ProductAssociationEnum::ALTERNATE,
        };

        // Use the synchronous manager for API endpoints (or use $product->associate() for async)
        $this->associationManager->associate($product, $targetProduct, $type);

        return response()->json([
            'message' => 'Association created successfully',
        ]);
    }

    /**
     * Remove an association.
     * 
     * DELETE /api/products/{product}/associations/{targetProduct}
     */
    public function destroy(Product $product, Product $targetProduct, Request $request)
    {
        $type = $request->get('type'); // Optional: if provided, only remove that type

        $enumType = null;
        if ($type) {
            $enumType = match($type) {
                'cross-sell' => ProductAssociationEnum::CROSS_SELL,
                'up-sell' => ProductAssociationEnum::UP_SELL,
                'alternate' => ProductAssociationEnum::ALTERNATE,
                default => null,
            };
        }

        $this->associationManager->dissociate($product, $targetProduct, $enumType);

        return response()->json([
            'message' => 'Association removed successfully',
        ]);
    }

    /**
     * Get all associations for a product.
     * 
     * GET /api/products/{product}/associations
     */
    public function index(Product $product, Request $request)
    {
        $type = $request->get('type'); // Optional filter by type

        $query = $product->associations()->with('target.variants.prices', 'target.images');

        if ($type) {
            $query->type($type);
        }

        $associations = $query->get()->map(function ($association) {
            return [
                'id' => $association->id,
                'type' => $association->type,
                'target_product' => [
                    'id' => $association->target->id,
                    'name' => $association->target->translateAttribute('name'),
                    'slug' => $association->target->urls->first()?->slug,
                ],
            ];
        });

        return response()->json([
            'associations' => $associations,
        ]);
    }
}

