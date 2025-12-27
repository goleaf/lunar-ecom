<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\AvailabilityBooking;
use App\Models\AvailabilityRule;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ProductAvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Display availability calendar.
     */
    public function calendar(Product $product, Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $availability = $this->availabilityService->getAvailableDates(
            $product,
            $startDate,
            $endDate
        );

        $bookings = AvailabilityBooking::where('product_id', $product->id)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with(['customer', 'order'])
            ->get();

        return view('admin.products.availability.calendar', compact(
            'product',
            'availability',
            'bookings',
            'month'
        ));
    }

    /**
     * Store availability rule.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'availability_type' => 'required|in:date_range,specific_dates,recurring,always_available',
            'start_date' => 'nullable|date|required_if:availability_type,date_range',
            'end_date' => 'nullable|date|after:start_date',
            'available_dates' => 'nullable|array',
            'unavailable_dates' => 'nullable|array',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|array',
            'max_quantity_per_date' => 'nullable|integer|min:1',
            'total_quantity' => 'nullable|integer|min:1',
            'available_from' => 'nullable|date_format:H:i',
            'available_until' => 'nullable|date_format:H:i',
            'slot_duration_minutes' => 'nullable|integer|min:1',
            'timezone' => 'nullable|string|max:50',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        $availability = ProductAvailability::create(array_merge($validated, [
            'product_id' => $product->id,
            'is_active' => true,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Availability rule created successfully.',
            'availability' => $availability,
        ]);
    }

    /**
     * Update availability rule.
     */
    public function update(Request $request, Product $product, ProductAvailability $availability): JsonResponse
    {
        $validated = $request->validate([
            'availability_type' => 'required|in:date_range,specific_dates,recurring,always_available',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'available_dates' => 'nullable|array',
            'unavailable_dates' => 'nullable|array',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|array',
            'max_quantity_per_date' => 'nullable|integer|min:1',
            'total_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'timezone' => 'nullable|string|max:50',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        $availability->update($validated);

        // Notify affected bookings
        $this->availabilityService->notifyAvailabilityChanges($product, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Availability rule updated successfully.',
            'availability' => $availability->fresh(),
        ]);
    }

    /**
     * Store availability rule (business rule).
     */
    public function storeRule(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'rule_type' => 'required|in:minimum_rental_period,maximum_rental_period,lead_time,buffer_time,cancellation_policy,blackout_date,special_pricing',
            'rule_config' => 'required|array',
            'rule_start_date' => 'nullable|date',
            'rule_end_date' => 'nullable|date|after:rule_start_date',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        $rule = AvailabilityRule::create(array_merge($validated, [
            'product_id' => $product->id,
            'is_active' => true,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Availability rule created successfully.',
            'rule' => $rule,
        ]);
    }

    /**
     * Get bookings for a date range.
     */
    public function bookings(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'nullable|in:pending,confirmed,cancelled,completed,no_show',
        ]);

        $bookings = AvailabilityBooking::where('product_id', $product->id)
            ->when($validated['start_date'] ?? null, function ($q) use ($validated) {
                $q->where('start_date', '>=', $validated['start_date']);
            })
            ->when($validated['end_date'] ?? null, function ($q) use ($validated) {
                $q->where('end_date', '<=', $validated['end_date']);
            })
            ->when($validated['status'] ?? null, function ($q) use ($validated) {
                $q->where('status', $validated['status']);
            })
            ->with(['customer', 'order', 'productVariant'])
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ]);
    }
}


