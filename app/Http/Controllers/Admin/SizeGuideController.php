<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SizeGuide;
use App\Models\SizeChart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SizeGuideController extends Controller
{
    /**
     * Display a listing of size guides.
     */
    public function index()
    {
        $sizeGuides = SizeGuide::with(['category', 'brand'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.size-guides.index', compact('sizeGuides'));
    }

    /**
     * Show the form for creating a new size guide.
     */
    public function create()
    {
        $categories = \Lunar\Models\Collection::defaultOrder()->get();
        $brands = \Lunar\Models\Brand::orderBy('name')->get();

        return view('admin.size-guides.create', compact('categories', 'brands'));
    }

    /**
     * Store a newly created size guide.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'measurement_unit' => 'required|in:cm,inches',
            'category_id' => 'nullable|exists:lunar_collections,id',
            'brand_id' => 'nullable|exists:lunar_brands,id',
            'region' => 'nullable|string|max:50',
            'supported_regions' => 'nullable|array',
            'size_system' => 'required|in:us,eu,uk,asia,custom',
            'size_labels' => 'nullable|array',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'conversion_table' => 'nullable|array',
        ]);

        $sizeGuide = SizeGuide::create($validated);

        return redirect()->route('admin.size-guides.show', $sizeGuide)
            ->with('success', 'Size guide created successfully.');
    }

    /**
     * Display the specified size guide.
     */
    public function show(SizeGuide $sizeGuide)
    {
        $sizeGuide->load(['sizeCharts', 'category', 'brand', 'products']);
        
        return view('admin.size-guides.show', compact('sizeGuide'));
    }

    /**
     * Show the form for editing the specified size guide.
     */
    public function edit(SizeGuide $sizeGuide)
    {
        $categories = \Lunar\Models\Collection::defaultOrder()->get();
        $brands = \Lunar\Models\Brand::orderBy('name')->get();
        $sizeGuide->load('sizeCharts');

        return view('admin.size-guides.edit', compact('sizeGuide', 'categories', 'brands'));
    }

    /**
     * Update the specified size guide.
     */
    public function update(Request $request, SizeGuide $sizeGuide)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'measurement_unit' => 'required|in:cm,inches',
            'category_id' => 'nullable|exists:lunar_collections,id',
            'brand_id' => 'nullable|exists:lunar_brands,id',
            'region' => 'nullable|string|max:50',
            'supported_regions' => 'nullable|array',
            'size_system' => 'required|in:us,eu,uk,asia,custom',
            'size_labels' => 'nullable|array',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'conversion_table' => 'nullable|array',
        ]);

        $sizeGuide->update($validated);

        return redirect()->route('admin.size-guides.show', $sizeGuide)
            ->with('success', 'Size guide updated successfully.');
    }

    /**
     * Remove the specified size guide.
     */
    public function destroy(SizeGuide $sizeGuide)
    {
        $sizeGuide->delete();

        return redirect()->route('admin.size-guides.index')
            ->with('success', 'Size guide deleted successfully.');
    }

    /**
     * Add size chart to size guide.
     */
    public function addSizeChart(Request $request, SizeGuide $sizeGuide): JsonResponse
    {
        $validated = $request->validate([
            'size_name' => 'required|string|max:50',
            'size_code' => 'nullable|string|max:50',
            'size_order' => 'nullable|integer|min:0',
            'measurements' => 'required|array',
            'size_min' => 'nullable|integer',
            'size_max' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $sizeChart = SizeChart::create(array_merge($validated, [
            'size_guide_id' => $sizeGuide->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Size chart added successfully.',
            'size_chart' => $sizeChart,
        ]);
    }

    /**
     * Update size chart.
     */
    public function updateSizeChart(Request $request, SizeGuide $sizeGuide, SizeChart $sizeChart): JsonResponse
    {
        $validated = $request->validate([
            'size_name' => 'required|string|max:50',
            'size_code' => 'nullable|string|max:50',
            'size_order' => 'nullable|integer|min:0',
            'measurements' => 'required|array',
            'size_min' => 'nullable|integer',
            'size_max' => 'nullable|integer',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $sizeChart->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Size chart updated successfully.',
            'size_chart' => $sizeChart->fresh(),
        ]);
    }

    /**
     * Delete size chart.
     */
    public function deleteSizeChart(SizeGuide $sizeGuide, SizeChart $sizeChart): JsonResponse
    {
        $sizeChart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Size chart deleted successfully.',
        ]);
    }
}


