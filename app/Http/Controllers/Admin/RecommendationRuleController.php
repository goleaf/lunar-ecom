<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\RecommendationRuleResource as FilamentRecommendationRuleResource;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RecommendationRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for managing recommendation rules.
 */
class RecommendationRuleController extends Controller
{
    /**
     * Display a listing of recommendation rules.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request)
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        // Prefer Filament for the admin UI. Keep JSON support for internal tooling.
        if (! $request->wantsJson()) {
            return redirect()->route('filament.admin.resources.' . FilamentRecommendationRuleResource::getSlug() . '.index', $request->query());
        }

        $query = RecommendationRule::with(['sourceProduct', 'recommendedProduct'])
            ->orderByDesc('priority')
            ->orderByDesc('created_at');

        if ($request->has('source_product_id')) {
            $query->where('source_product_id', $request->get('source_product_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        $rules = $query->paginate(20);

        return response()->json($rules);
    }

    /**
     * Store a newly created recommendation rule.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'source_product_id' => 'nullable|exists:products,id',
            'recommended_product_id' => 'required|exists:products,id',
            'rule_type' => 'required|string|in:manual,category,attribute',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'nullable|array',
            'priority' => 'integer|min:0|max:100',
            'is_active' => 'boolean',
            'ab_test_variant' => 'nullable|string',
        ]);

        $rule = RecommendationRule::create($validated);

        return response()->json([
            'message' => 'Recommendation rule created successfully',
            'rule' => $rule->load(['sourceProduct', 'recommendedProduct']),
        ], 201);
    }

    /**
     * Update the specified recommendation rule.
     *
     * @param  Request  $request
     * @param  RecommendationRule  $rule
     * @return JsonResponse
     */
    public function update(Request $request, RecommendationRule $rule): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'source_product_id' => 'nullable|exists:products,id',
            'recommended_product_id' => 'exists:products,id',
            'rule_type' => 'string|in:manual,category,attribute',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'nullable|array',
            'priority' => 'integer|min:0|max:100',
            'is_active' => 'boolean',
            'ab_test_variant' => 'nullable|string',
        ]);

        $rule->update($validated);

        return response()->json([
            'message' => 'Recommendation rule updated successfully',
            'rule' => $rule->fresh(['sourceProduct', 'recommendedProduct']),
        ]);
    }

    /**
     * Remove the specified recommendation rule.
     *
     * @param  RecommendationRule  $rule
     * @return JsonResponse
     */
    public function destroy(RecommendationRule $rule): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $rule->delete();

        return response()->json([
            'message' => 'Recommendation rule deleted successfully',
        ]);
    }

    /**
     * Get recommendation analytics.
     *
     * @return JsonResponse
     */
    public function analytics(): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $stats = [
            'total_rules' => RecommendationRule::count(),
            'active_rules' => RecommendationRule::active()->count(),
            'total_displays' => RecommendationRule::sum('display_count'),
            'total_clicks' => RecommendationRule::sum('click_count'),
            'average_conversion_rate' => RecommendationRule::avg('conversion_rate'),
            'top_rules' => RecommendationRule::orderByDesc('conversion_rate')
                ->limit(10)
                ->with(['sourceProduct', 'recommendedProduct'])
                ->get(),
        ];

        return response()->json($stats);
    }
}
