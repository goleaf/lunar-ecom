<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CustomizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductCustomizationController extends Controller
{
    public function __construct(
        protected CustomizationService $customizationService
    ) {}

    /**
     * Get customizations for a product.
     */
    public function index(Product $product): JsonResponse
    {
        $customizations = $this->customizationService->getProductCustomizations($product);
        $examples = $this->customizationService->getExamples($product);
        $templates = $this->customizationService->getTemplates();

        return response()->json([
            'success' => true,
            'customizations' => $customizations,
            'examples' => $examples,
            'templates' => $templates,
        ]);
    }

    /**
     * Validate customization values.
     */
    public function validate(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'customizations' => 'required|array',
        ]);

        $result = $this->customizationService->validateCustomization(
            $product,
            $validated['customizations']
        );

        return response()->json($result);
    }

    /**
     * Generate preview.
     */
    public function preview(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'customizations' => 'required|array',
        ]);

        try {
            $previewImage = $this->customizationService->generatePreview(
                $product,
                $validated['customizations']
            );

            return response()->json([
                'success' => true,
                'preview' => $previewImage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload customization image.
     */
    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'field_name' => 'required|string',
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        $customization = \App\Models\ProductCustomization::where('product_id', $product->id)
            ->where('field_name', $validated['field_name'])
            ->where('customization_type', 'image')
            ->first();

        if (!$customization) {
            return response()->json([
                'success' => false,
                'message' => 'Customization field not found.',
            ], 404);
        }

        $validation = $this->customizationService->validateCustomizationValue(
            $customization,
            $request->file('image')
        );

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['error'],
            ], 422);
        }

        // Process and store image
        $imageData = $this->customizationService->processImageUpload(
            $request->file('image'),
            $customization
        );

        return response()->json([
            'success' => true,
            'image' => [
                'path' => $imageData['path'],
                'url' => \Storage::url($imageData['path']),
                'width' => $imageData['width'],
                'height' => $imageData['height'],
            ],
        ]);
    }

    /**
     * Get customization templates.
     */
    public function templates(Request $request): JsonResponse
    {
        $category = $request->input('category');
        $templates = $this->customizationService->getTemplates($category);

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Get customization examples.
     */
    public function examples(Product $product): JsonResponse
    {
        $examples = $this->customizationService->getExamples($product);

        return response()->json([
            'success' => true,
            'examples' => $examples,
        ]);
    }
}



