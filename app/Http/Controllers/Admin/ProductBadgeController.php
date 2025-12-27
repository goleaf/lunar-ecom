<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBadge;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductBadgeController extends Controller
{
    public function __construct(
        protected BadgeService $badgeService
    ) {}

    /**
     * Display a listing of badges.
     */
    public function index()
    {
        $badges = ProductBadge::withCount('assignments')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.badges.index', compact('badges'));
    }

    /**
     * Show the form for creating a new badge.
     */
    public function create()
    {
        return view('admin.badges.create');
    }

    /**
     * Store a newly created badge.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_badges,name',
            'handle' => 'nullable|string|max:255|unique:product_badges,handle',
            'type' => 'required|in:new,sale,hot,limited,exclusive,custom',
            'description' => 'nullable|string',
            'label' => 'nullable|string|max:255',
            'color' => 'required|string|max:7',
            'background_color' => 'required|string|max:7',
            'border_color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'position' => 'required|in:top-left,top-right,bottom-left,bottom-right,center',
            'style' => 'required|in:rounded,square,pill,custom',
            'font_size' => 'nullable|integer|min:8|max:24',
            'padding_x' => 'nullable|integer|min:0|max:20',
            'padding_y' => 'nullable|integer|min:0|max:20',
            'border_radius' => 'nullable|integer|min:0|max:50',
            'show_icon' => 'boolean',
            'animated' => 'boolean',
            'animation_type' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0|max:100',
            'max_display_count' => 'nullable|integer|min:1',
            'auto_assign' => 'boolean',
            'assignment_rules' => 'nullable|array',
            'display_conditions' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $badge = ProductBadge::create($validated);

        return redirect()->route('admin.badges.index')
            ->with('success', 'Badge created successfully.');
    }

    /**
     * Display the specified badge.
     */
    public function show(ProductBadge $badge)
    {
        $badge->load(['assignments.product', 'rules', 'performance']);
        
        return view('admin.badges.show', compact('badge'));
    }

    /**
     * Show the form for editing the specified badge.
     */
    public function edit(ProductBadge $badge)
    {
        return view('admin.badges.edit', compact('badge'));
    }

    /**
     * Update the specified badge.
     */
    public function update(Request $request, ProductBadge $badge)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_badges,name,' . $badge->id,
            'handle' => 'nullable|string|max:255|unique:product_badges,handle,' . $badge->id,
            'type' => 'required|in:new,sale,hot,limited,exclusive,custom',
            'description' => 'nullable|string',
            'label' => 'nullable|string|max:255',
            'color' => 'required|string|max:7',
            'background_color' => 'required|string|max:7',
            'border_color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'position' => 'required|in:top-left,top-right,bottom-left,bottom-right,center',
            'style' => 'required|in:rounded,square,pill,custom',
            'font_size' => 'nullable|integer|min:8|max:24',
            'padding_x' => 'nullable|integer|min:0|max:20',
            'padding_y' => 'nullable|integer|min:0|max:20',
            'border_radius' => 'nullable|integer|min:0|max:50',
            'show_icon' => 'boolean',
            'animated' => 'boolean',
            'animation_type' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0|max:100',
            'max_display_count' => 'nullable|integer|min:1',
            'auto_assign' => 'boolean',
            'assignment_rules' => 'nullable|array',
            'display_conditions' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $badge->update($validated);

        return redirect()->route('admin.badges.index')
            ->with('success', 'Badge updated successfully.');
    }

    /**
     * Remove the specified badge.
     */
    public function destroy(ProductBadge $badge)
    {
        $badge->delete();

        return redirect()->route('admin.badges.index')
            ->with('success', 'Badge deleted successfully.');
    }

    /**
     * Preview badge styling.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->only([
            'label', 'color', 'background_color', 'border_color',
            'icon', 'style', 'font_size', 'padding_x', 'padding_y',
            'border_radius', 'show_icon', 'animated', 'animation_type'
        ]);

        $badge = new ProductBadge($data);
        $badge->name = $data['label'] ?? 'Preview';

        return response()->json([
            'html' => view('admin.badges.preview', compact('badge'))->render(),
            'styles' => $badge->getInlineStyles(),
            'classes' => $badge->getCssClasses(),
        ]);
    }

    /**
     * Get badge performance data.
     */
    public function performance(ProductBadge $badge, Request $request): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? \Carbon\Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->input('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))
            : now();

        $performance = $this->badgeService->getBadgePerformance($badge, $startDate, $endDate);

        return response()->json($performance);
    }
}
