<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\SizeGuideResource as FilamentSizeGuideResource;
use App\Http\Controllers\Controller;
use App\Models\SizeGuide;
use App\Models\SizeChart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SizeGuideController extends Controller
{
    /**
     * Display a listing of size guides.
     */
    public function index(): RedirectResponse
    {
        // Prefer Filament for the admin UI.
        return redirect()->to(FilamentSizeGuideResource::getUrl('index'));
    }

    /**
     * Show the form for creating a new size guide.
     */
    public function create(): RedirectResponse
    {
        // Prefer Filament for the admin UI.
        return redirect()->to(FilamentSizeGuideResource::getUrl('create'));
    }

    /**
     * Store a newly created size guide.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'measurement_unit' => 'required|in:cm,inches',
            'category_id' => 'nullable|exists:collections,id',
            'brand_id' => 'nullable|exists:brands,id',
            'region' => 'nullable|string|max:50',
            'supported_regions' => 'nullable|array',
            'size_system' => 'required|in:us,eu,uk,asia,custom',
            'size_labels' => 'nullable|array',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'conversion_table' => 'nullable|array',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('size-guides', 'public');
        } else {
            unset($validated['image']);
        }

        $sizeGuide = SizeGuide::create($validated);

        return redirect()->to(
            FilamentSizeGuideResource::getUrl('edit', ['record' => $sizeGuide])
        );
    }

    /**
     * Display the specified size guide.
     */
    public function show(SizeGuide $sizeGuide): RedirectResponse
    {
        // Prefer Filament for the admin UI.
        return redirect()->to(
            FilamentSizeGuideResource::getUrl('edit', ['record' => $sizeGuide])
        );
    }

    /**
     * Show the form for editing the specified size guide.
     */
    public function edit(SizeGuide $sizeGuide): RedirectResponse
    {
        // Prefer Filament for the admin UI.
        return redirect()->to(
            FilamentSizeGuideResource::getUrl('edit', ['record' => $sizeGuide])
        );
    }

    /**
     * Update the specified size guide.
     */
    public function update(Request $request, SizeGuide $sizeGuide): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'measurement_unit' => 'required|in:cm,inches',
            'category_id' => 'nullable|exists:collections,id',
            'brand_id' => 'nullable|exists:brands,id',
            'region' => 'nullable|string|max:50',
            'supported_regions' => 'nullable|array',
            'size_system' => 'required|in:us,eu,uk,asia,custom',
            'size_labels' => 'nullable|array',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'conversion_table' => 'nullable|array',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('size-guides', 'public');
        } else {
            unset($validated['image']);
        }

        $sizeGuide->update($validated);

        return redirect()->to(
            FilamentSizeGuideResource::getUrl('edit', ['record' => $sizeGuide])
        );
    }

    /**
     * Remove the specified size guide.
     */
    public function destroy(SizeGuide $sizeGuide): RedirectResponse
    {
        $sizeGuide->delete();

        return redirect()->to(FilamentSizeGuideResource::getUrl('index'));
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


