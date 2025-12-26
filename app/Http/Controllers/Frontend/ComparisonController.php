<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ComparisonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Controller for product comparison functionality.
 */
class ComparisonController extends Controller
{
    public function __construct(
        protected ComparisonService $comparisonService
    ) {}

    /**
     * Display comparison page.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request)
    {
        $selectedAttributes = $request->get('attributes');
        $comparisonTable = $this->comparisonService->getComparisonTable($selectedAttributes);

        if ($request->wantsJson()) {
            return response()->json($comparisonTable);
        }

        $attributes = $this->comparisonService->getComparisonAttributes($selectedAttributes);
        $allAttributes = $this->comparisonService->getComparisonAttributes(); // All available attributes

        return view('storefront.comparison.index', compact(
            'comparisonTable',
            'attributes',
            'allAttributes',
            'selectedAttributes'
        ));
    }

    /**
     * Add product to comparison.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:lunar_products,id',
        ]);

        try {
            $result = $this->comparisonService->addToComparison($validated['product_id']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove product from comparison.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function remove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:lunar_products,id',
        ]);

        $result = $this->comparisonService->removeFromComparison($validated['product_id']);

        return response()->json($result);
    }

    /**
     * Clear comparison.
     *
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        $this->comparisonService->clearComparison();

        return response()->json([
            'success' => true,
            'message' => 'Comparison cleared',
        ]);
    }

    /**
     * Get comparison count.
     *
     * @return JsonResponse
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'count' => $this->comparisonService->getComparisonCount(),
        ]);
    }

    /**
     * Check if product is in comparison.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:lunar_products,id',
        ]);

        return response()->json([
            'in_comparison' => $this->comparisonService->isInComparison($validated['product_id']),
        ]);
    }

    /**
     * Get comparison products.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function products(Request $request): JsonResponse
    {
        $selectedAttributes = $request->get('attributes');
        $products = $this->comparisonService->getComparisonProducts($selectedAttributes);

        return response()->json([
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->translateAttribute('name'),
                    'slug' => $product->urls->first()?->slug,
                    'image' => $product->thumbnail?->getUrl(),
                    'price' => $this->comparisonService->getProductPrice($product),
                ];
            }),
        ]);
    }

    /**
     * Export comparison as PDF.
     *
     * @param  Request  $request
     * @return Response|JsonResponse
     */
    public function exportPdf(Request $request)
    {
        $selectedAttributes = $request->get('attributes');
        $comparisonTable = $this->comparisonService->getComparisonTable($selectedAttributes);

        // Use dompdf to generate PDF (requires barryvdh/laravel-dompdf package)
        try {
            $html = view('storefront.comparison.pdf', compact('comparisonTable'))->render();
            
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                return $pdf->download('product-comparison-' . date('Y-m-d') . '.pdf');
            } else {
                // Fallback: return HTML view if PDF library not installed
                return response($html)->header('Content-Type', 'text/html');
            }
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'PDF export failed: ' . $e->getMessage(),
                ], 500);
            }
            
            // Fallback to HTML
            $html = view('storefront.comparison.pdf', compact('comparisonTable'))->render();
            return response($html)->header('Content-Type', 'text/html');
        }
    }
}
