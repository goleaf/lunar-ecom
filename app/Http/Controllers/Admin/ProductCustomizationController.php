<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCustomization;
use App\Models\CustomizationTemplate;
use App\Models\CustomizationExample;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductCustomizationController extends Controller
{
    /**
     * Display customizations for a product.
     */
    public function index(Product $product)
    {
        $customizations = ProductCustomization::where('product_id', $product->id)
            ->orderBy('display_order')
            ->get();

        return view('admin.products.customizations.index', compact('product', 'customizations'));
    }

    /**
     * Store a new customization.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'customization_type' => 'required|in:text,image,option,color,number,date',
            'field_name' => 'required|string|max:255|unique:lunar_product_customizations,field_name,NULL,id,product_id,' . $product->id,
            'field_label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'min_length' => 'nullable|integer|min:0',
            'max_length' => 'nullable|integer|min:1',
            'pattern' => 'nullable|string|max:255',
            'allowed_values' => 'nullable|array',
            'allowed_formats' => 'nullable|array',
            'max_file_size_kb' => 'nullable|integer|min:1',
            'min_width' => 'nullable|integer|min:1',
            'max_width' => 'nullable|integer|min:1',
            'min_height' => 'nullable|integer|min:1',
            'max_height' => 'nullable|integer|min:1',
            'aspect_ratio_width' => 'nullable|integer|min:1',
            'aspect_ratio_height' => 'nullable|integer|min:1',
            'price_modifier' => 'nullable|numeric|min:0',
            'price_modifier_type' => 'required|in:fixed,per_character,per_image',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_in_preview' => 'boolean',
            'preview_settings' => 'nullable|array',
            'template_image' => 'nullable|image|max:2048',
            'example_values' => 'nullable|array',
        ]);

        $customization = ProductCustomization::create(array_merge($validated, [
            'product_id' => $product->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Customization created successfully.',
            'customization' => $customization,
        ]);
    }

    /**
     * Update a customization.
     */
    public function update(Request $request, Product $product, ProductCustomization $customization): JsonResponse
    {
        $validated = $request->validate([
            'field_label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'min_length' => 'nullable|integer|min:0',
            'max_length' => 'nullable|integer|min:1',
            'pattern' => 'nullable|string|max:255',
            'allowed_values' => 'nullable|array',
            'allowed_formats' => 'nullable|array',
            'max_file_size_kb' => 'nullable|integer|min:1',
            'min_width' => 'nullable|integer|min:1',
            'max_width' => 'nullable|integer|min:1',
            'min_height' => 'nullable|integer|min:1',
            'max_height' => 'nullable|integer|min:1',
            'aspect_ratio_width' => 'nullable|integer|min:1',
            'aspect_ratio_height' => 'nullable|integer|min:1',
            'price_modifier' => 'nullable|numeric|min:0',
            'price_modifier_type' => 'required|in:fixed,per_character,per_image',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_in_preview' => 'boolean',
            'preview_settings' => 'nullable|array',
            'template_image' => 'nullable|image|max:2048',
            'example_values' => 'nullable|array',
        ]);

        $customization->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customization updated successfully.',
            'customization' => $customization->fresh(),
        ]);
    }

    /**
     * Delete a customization.
     */
    public function destroy(Product $product, ProductCustomization $customization): JsonResponse
    {
        $customization->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customization deleted successfully.',
        ]);
    }

    /**
     * Manage customization templates.
     */
    public function templates()
    {
        $templates = CustomizationTemplate::orderBy('category')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.customizations.templates', compact('templates'));
    }

    /**
     * Store a customization template.
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'template_data' => 'required|array',
            'preview_image' => 'nullable|image|max:2048',
        ]);

        $template = CustomizationTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully.',
            'template' => $template,
        ]);
    }

    /**
     * Manage customization examples.
     */
    public function examples(Product $product)
    {
        $examples = CustomizationExample::where('product_id', $product->id)
            ->orderBy('display_order')
            ->get();

        return view('admin.products.customizations.examples', compact('product', 'examples'));
    }

    /**
     * Store a customization example.
     */
    public function storeExample(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'customization_id' => 'nullable|exists:lunar_product_customizations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'example_image' => 'required|image|max:2048',
            'customization_values' => 'nullable|array',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $example = CustomizationExample::create(array_merge($validated, [
            'product_id' => $product->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Example created successfully.',
            'example' => $example,
        ]);
    }
}


