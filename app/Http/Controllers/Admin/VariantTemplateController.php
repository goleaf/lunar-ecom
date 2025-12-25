<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VariantTemplate;
use App\Services\VariantMatrixGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller for managing variant templates/presets.
 */
class VariantTemplateController extends Controller
{
    /**
     * List templates.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = VariantTemplate::query();

        if ($request->has('product_type_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('product_type_id', $request->input('product_type_id'))
                  ->orWhereNull('product_type_id');
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $templates = $query->where('is_active', true)->get();

        return response()->json([
            'templates' => $templates,
        ]);
    }

    /**
     * Create template.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:preset,template,pattern',
            'product_type_id' => 'nullable|exists:' . config('lunar.database.table_prefix') . 'product_types,id',
            'default_combination' => 'nullable|array',
            'default_fields' => 'nullable|array',
            'attribute_config' => 'nullable|array',
        ]);

        $template = VariantTemplate::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Template created',
            'template' => $template,
        ]);
    }

    /**
     * Apply template to product.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @param  VariantTemplate  $template
     * @return JsonResponse
     */
    public function apply(Request $request, Product $product, VariantTemplate $template): JsonResponse
    {
        $service = app(VariantMatrixGeneratorService::class);
        
        $variants = $service->generateFromTemplate($product, $template, [
            'combination' => $request->input('combination', []),
            'fields' => $request->input('fields', []),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Generated {$variants->count()} variants from template",
            'variants' => $variants,
        ]);
    }

    /**
     * Update template.
     *
     * @param  Request  $request
     * @param  VariantTemplate  $template
     * @return JsonResponse
     */
    public function update(Request $request, VariantTemplate $template): JsonResponse
    {
        $template->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Template updated',
            'template' => $template,
        ]);
    }

    /**
     * Delete template.
     *
     * @param  VariantTemplate  $template
     * @return JsonResponse
     */
    public function destroy(VariantTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted',
        ]);
    }
}

