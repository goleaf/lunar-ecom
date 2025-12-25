<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateVariantsRequest;
use App\Http\Requests\StoreVariantRequest;
use App\Http\Requests\UpdateVariantRequest;
use App\Lunar\Variants\VariantHelper;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing product variants.
 */
class VariantController extends Controller
{
    public function __construct(
        protected VariantGenerator $variantGenerator
    ) {}

    /**
     * Generate variants for a product.
     * 
     * @param GenerateVariantsRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function generate(GenerateVariantsRequest $request, Product $product): JsonResponse
    {
        $this->authorize('create', ProductVariant::class);
        
        $optionIds = $request->input('option_ids');
        $defaults = $request->input('defaults', []);

        $variants = $this->variantGenerator->generateVariants($product, $optionIds, $defaults);

        return response()->json([
            'success' => true,
            'message' => "Generated {$variants->count()} variants",
            'variants' => $variants->map(function ($variant) {
                return VariantHelper::getDisplayData($variant);
            }),
        ]);
    }

    /**
     * Store a new variant.
     * 
     * @param StoreVariantRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function store(StoreVariantRequest $request, Product $product): JsonResponse
    {
        $this->authorize('create', ProductVariant::class);
        
        $data = $request->validated();

        // Get tax class if not provided
        if (!isset($data['tax_class_id'])) {
            $taxClass = \Lunar\Models\TaxClass::where('default', true)->first();
            if (!$taxClass) {
                return response()->json([
                    'error' => 'No default tax class found',
                ], 422);
            }
            $data['tax_class_id'] = $taxClass->id;
        }

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $data['sku'],
            'gtin' => $data['gtin'] ?? null,
            'mpn' => $data['mpn'] ?? null,
            'ean' => $data['ean'] ?? null,
            'unit_quantity' => $data['unit_quantity'],
            'min_quantity' => $data['min_quantity'] ?? 1,
            'quantity_increment' => $data['quantity_increment'] ?? 1,
            'stock' => $data['stock'],
            'backorder' => $data['backorder'] ?? 0,
            'purchasable' => $data['purchasable'],
            'shippable' => $data['shippable'] ?? true,
            'tax_class_id' => $data['tax_class_id'],
            'enabled' => $data['enabled'] ?? true,
            'weight' => $data['weight'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'price_override' => $data['price_override'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,
            'compare_at_price' => $data['compare_at_price'] ?? null,
        ]);

        // Attach option values
        if (isset($data['option_values'])) {
            $variant->variantOptions()->attach($data['option_values']);
        }

        // Set pricing
        if (isset($data['prices'])) {
            foreach ($data['prices'] as $priceData) {
                VariantHelper::setPrice(
                    $variant,
                    $priceData['price'],
                    $priceData['currency_id'],
                    $priceData['compare_price'] ?? null,
                    $priceData['min_quantity'] ?? 1,
                    $priceData['tier'] ?? 1
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Variant created successfully',
            'variant' => VariantHelper::getDisplayData($variant),
        ], 201);
    }

    /**
     * Update a variant.
     * 
     * @param UpdateVariantRequest $request
     * @param ProductVariant $variant
     * @return JsonResponse
     */
    public function update(UpdateVariantRequest $request, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $variant);
        
        $data = $request->validated();

        $variant->update($data);

        // Update option values if provided
        if (isset($data['option_values'])) {
            $variant->variantOptions()->sync($data['option_values']);
        }

        // Update pricing if provided
        if (isset($data['prices'])) {
            foreach ($data['prices'] as $priceData) {
                VariantHelper::setPrice(
                    $variant,
                    $priceData['price'],
                    $priceData['currency_id'],
                    $priceData['compare_price'] ?? null,
                    $priceData['min_quantity'] ?? 1,
                    $priceData['tier'] ?? 1
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Variant updated successfully',
            'variant' => VariantHelper::getDisplayData($variant),
        ]);
    }

    /**
     * Get variant details.
     * 
     * @param ProductVariant $variant
     * @return JsonResponse
     */
    public function show(ProductVariant $variant): JsonResponse
    {
        return response()->json([
            'variant' => VariantHelper::getDisplayData($variant),
        ]);
    }

    /**
     * Update variant stock.
     * 
     * @param Request $request
     * @param ProductVariant $variant
     * @return JsonResponse
     */
    public function updateStock(Request $request, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $variant);
        
        $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update(['stock' => $request->input('stock')]);

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'stock' => VariantHelper::getStockStatus($variant),
        ]);
    }

    /**
     * Attach image to variant.
     * 
     * @param Request $request
     * @param ProductVariant $variant
     * @return JsonResponse
     */
    public function attachImage(Request $request, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $variant);
        
        $request->validate([
            'media_id' => 'required|exists:media,id',
            'primary' => 'nullable|boolean',
        ]);

        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($request->input('media_id'));

        // Ensure media belongs to the product
        if ($media->model_type !== Product::class || $media->model_id !== $variant->product_id) {
            return response()->json([
                'error' => 'Media does not belong to the product',
            ], 422);
        }

        $variant->attachImage($media, $request->input('primary', false));

        return response()->json([
            'success' => true,
            'message' => 'Image attached successfully',
            'images' => VariantHelper::getImages($variant),
        ]);
    }

    /**
     * Detach image from variant.
     * 
     * @param Request $request
     * @param ProductVariant $variant
     * @param int $mediaId
     * @return JsonResponse
     */
    public function detachImage(ProductVariant $variant, int $mediaId): JsonResponse
    {
        $this->authorize('update', $variant);
        
        $variant->detachImage($mediaId);

        return response()->json([
            'success' => true,
            'message' => 'Image detached successfully',
            'images' => VariantHelper::getImages($variant),
        ]);
    }

    /**
     * Set primary image for variant.
     * 
     * @param Request $request
     * @param ProductVariant $variant
     * @return JsonResponse
     */
    public function setPrimaryImage(Request $request, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $variant);
        
        $request->validate([
            'media_id' => 'required|exists:media,id',
        ]);

        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($request->input('media_id'));
        $variant->setPrimaryImage($media);

        return response()->json([
            'success' => true,
            'message' => 'Primary image set successfully',
            'images' => VariantHelper::getImages($variant),
        ]);
    }

    /**
     * Get all variants for a product.
     * 
     * @param Product $product
     * @return JsonResponse
     */
    public function index(Product $product): JsonResponse
    {
        $variants = VariantHelper::getProductVariants($product);

        return response()->json([
            'variants' => $variants,
        ]);
    }

    /**
     * Delete a variant.
     * 
     * @param ProductVariant $variant
     * @return JsonResponse
     */
    public function destroy(ProductVariant $variant): JsonResponse
    {
        $this->authorize('delete', $variant);
        
        $variant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variant deleted successfully',
        ]);
    }
}

