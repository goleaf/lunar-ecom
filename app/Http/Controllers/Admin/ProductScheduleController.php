<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSchedule;
use App\Services\ProductSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for product scheduling.
 */
class ProductScheduleController extends Controller
{
    public function __construct(
        protected ProductSchedulingService $schedulingService
    ) {}

    /**
     * Display product schedules.
     *
     * @param  Product  $product
     * @return \Illuminate\View\View
     */
    public function index(Product $product)
    {
        $schedules = ProductSchedule::where('product_id', $product->id)
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        return view('admin.products.schedules', compact('product', 'schedules'));
    }

    /**
     * Store a new schedule.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:publish,unpublish,flash_sale,seasonal,time_limited',
            'scheduled_at' => 'required|date|after:now',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'target_status' => 'nullable|string',
            'is_active' => 'boolean',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_percentage' => 'nullable|integer|min:0|max:100',
            'restore_original_price' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|in:daily,weekly,monthly,yearly',
            'send_notification' => 'boolean',
        ]);

        try {
            $schedule = $this->schedulingService->createSchedule($product, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'schedule' => $schedule,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a schedule.
     *
     * @param  Request  $request
     * @param  ProductSchedule  $schedule
     * @return JsonResponse
     */
    public function update(Request $request, ProductSchedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:publish,unpublish,flash_sale,seasonal,time_limited',
            'scheduled_at' => 'sometimes|date',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'target_status' => 'nullable|string',
            'is_active' => 'boolean',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_percentage' => 'nullable|integer|min:0|max:100',
            'restore_original_price' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|in:daily,weekly,monthly,yearly',
            'send_notification' => 'boolean',
        ]);

        try {
            $schedule->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'schedule' => $schedule->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a schedule.
     *
     * @param  ProductSchedule  $schedule
     * @return JsonResponse
     */
    public function destroy(ProductSchedule $schedule): JsonResponse
    {
        try {
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upcoming schedules.
     *
     * @return JsonResponse
     */
    public function upcoming(): JsonResponse
    {
        $schedules = ProductSchedule::upcoming()
            ->with('product')
            ->orderBy('scheduled_at')
            ->limit(50)
            ->get();

        return response()->json($schedules);
    }

    /**
     * Get active flash sales.
     *
     * @return JsonResponse
     */
    public function activeFlashSales(): JsonResponse
    {
        $schedules = ProductSchedule::where('type', 'flash_sale')
            ->where('is_active', true)
            ->where('scheduled_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->with('product')
            ->get();

        return response()->json($schedules);
    }
}

