<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBadge;
use App\Models\ProductBadgeRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductBadgeRuleController extends Controller
{
    /**
     * Display a listing of rules.
     */
    public function index()
    {
        $rules = ProductBadgeRule::with('badge')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.badges.rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new rule.
     */
    public function create()
    {
        $badges = ProductBadge::active()->orderBy('name')->get();
        
        return view('admin.badges.rules.create', compact('badges'));
    }

    /**
     * Store a newly created rule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'badge_id' => 'required|exists:lunar_product_badges,id',
            'condition_type' => 'required|in:manual,automatic',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'required|array',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $rule = ProductBadgeRule::create($validated);

        return redirect()->route('admin.badges.rules.index')
            ->with('success', 'Rule created successfully.');
    }

    /**
     * Display the specified rule.
     */
    public function show(ProductBadgeRule $rule)
    {
        $rule->load(['badge', 'assignments.product']);
        
        return view('admin.badges.rules.show', compact('rule'));
    }

    /**
     * Show the form for editing the specified rule.
     */
    public function edit(ProductBadgeRule $rule)
    {
        $badges = ProductBadge::active()->orderBy('name')->get();
        
        return view('admin.badges.rules.edit', compact('rule', 'badges'));
    }

    /**
     * Update the specified rule.
     */
    public function update(Request $request, ProductBadgeRule $rule)
    {
        $validated = $request->validate([
            'badge_id' => 'required|exists:lunar_product_badges,id',
            'condition_type' => 'required|in:manual,automatic',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'required|array',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $rule->update($validated);

        return redirect()->route('admin.badges.rules.index')
            ->with('success', 'Rule updated successfully.');
    }

    /**
     * Remove the specified rule.
     */
    public function destroy(ProductBadgeRule $rule)
    {
        $rule->delete();

        return redirect()->route('admin.badges.rules.index')
            ->with('success', 'Rule deleted successfully.');
    }

    /**
     * Test a rule against products.
     */
    public function test(ProductBadgeRule $rule, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        
        $service = app(\App\Services\BadgeService::class);
        $products = \App\Models\Product::where('status', 'published')
            ->limit($limit)
            ->get();

        $matches = [];
        foreach ($products as $product) {
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('evaluateRule');
            $method->setAccessible(true);
            
            if ($method->invoke($service, $product, $rule)) {
                $matches[] = [
                    'id' => $product->id,
                    'name' => $product->translateAttribute('name'),
                ];
            }
        }

        return response()->json([
            'matches' => $matches,
            'total_checked' => $products->count(),
            'total_matches' => count($matches),
        ]);
    }
}

