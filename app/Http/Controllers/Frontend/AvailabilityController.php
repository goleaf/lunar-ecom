<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Check date availability.
     */
    public function checkAvailability(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'quantity' => 'nullable|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
            'timezone' => 'nullable|string|max:50',
        ]);

        $date = Carbon::parse($validated['date']);
        $variant = $validated['variant_id'] 
            ? ProductVariant::find($validated['variant_id']) 
            : null;

        $availability = $this->availabilityService->checkDateAvailability(
            $product,
            $date,
            $validated['quantity'] ?? 1,
            $variant,
            $validated['timezone'] ?? null
        );

        return response()->json([
            'success' => true,
            'availability' => $availability,
        ]);
    }

    /**
     * Get available dates for a range.
     */
    public function getAvailableDates(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'variant_id' => 'nullable|exists:product_variants,id',
            'timezone' => 'nullable|string|max:50',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $variant = $validated['variant_id'] 
            ? ProductVariant::find($validated['variant_id']) 
            : null;

        $dates = $this->availabilityService->getAvailableDates(
            $product,
            $startDate,
            $endDate,
            $variant,
            $validated['timezone'] ?? null
        );

        return response()->json([
            'success' => true,
            'dates' => $dates,
        ]);
    }

    /**
     * Calculate rental pricing.
     */
    public function calculatePricing(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'quantity' => 'nullable|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : null;
        $variant = $validated['variant_id'] 
            ? ProductVariant::find($validated['variant_id']) 
            : null;

        $pricing = $this->availabilityService->calculateRentalPricing(
            $product,
            $variant,
            $startDate,
            $endDate,
            $validated['quantity'] ?? 1
        );

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Reserve a date (create booking).
     */
    public function reserveDate(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'timezone' => 'nullable|string|max:50',
        ]);

        try {
            $booking = $this->availabilityService->reserveDate($product, array_merge($validated, [
                'customer_id' => auth()->user()?->customer?->id,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Date reserved successfully.',
                'booking' => $booking,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}



