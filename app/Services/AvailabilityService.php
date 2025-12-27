<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductAvailability;
use App\Models\AvailabilityBooking;
use App\Models\AvailabilityRule;
use App\Models\AvailabilityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityService
{
    /**
     * Check if a date is available for booking.
     *
     * @param  Product  $product
     * @param  Carbon  $date
     * @param  int  $quantity
     * @param  ProductVariant|null  $variant
     * @param  string|null  $timezone
     * @return array  ['available' => bool, 'reason' => string, 'available_quantity' => int]
     */
    public function checkDateAvailability(
        Product $product,
        Carbon $date,
        int $quantity = 1,
        ?ProductVariant $variant = null,
        ?string $timezone = null
    ): array {
        // Convert date to product timezone
        $productTimezone = $this->getProductTimezone($product);
        $dateInTimezone = $this->convertToTimezone($date, $productTimezone, $timezone);

        // Get availability rules
        $availability = $this->getProductAvailability($product, $variant);

        if ($availability->isEmpty()) {
            return [
                'available' => false,
                'reason' => 'No availability rules configured',
                'available_quantity' => 0,
            ];
        }

        // Check each availability rule
        foreach ($availability->sortByDesc('priority') as $rule) {
            if (!$rule->isDateAvailable($dateInTimezone)) {
                continue; // This rule doesn't apply, check next
            }

            // Check quantity limits
            $bookedQuantity = $this->getBookedQuantity($product, $variant, $dateInTimezone);
            $maxQuantity = $rule->max_quantity_per_date ?? $rule->total_quantity ?? PHP_INT_MAX;
            $availableQuantity = max(0, $maxQuantity - $bookedQuantity);

            if ($availableQuantity < $quantity) {
                return [
                    'available' => false,
                    'reason' => 'Insufficient quantity available',
                    'available_quantity' => $availableQuantity,
                    'max_quantity' => $maxQuantity,
                    'booked_quantity' => $bookedQuantity,
                ];
            }

            // Check business rules
            $ruleCheck = $this->checkBusinessRules($product, $dateInTimezone, $quantity);
            if (!$ruleCheck['valid']) {
                return [
                    'available' => false,
                    'reason' => $ruleCheck['reason'],
                    'available_quantity' => $availableQuantity,
                ];
            }

            return [
                'available' => true,
                'reason' => 'Available',
                'available_quantity' => $availableQuantity,
                'max_quantity' => $maxQuantity,
                'booked_quantity' => $bookedQuantity,
            ];
        }

        return [
            'available' => false,
            'reason' => 'Date not available',
            'available_quantity' => 0,
        ];
    }

    /**
     * Get available dates for a date range.
     *
     * @param  Product  $product
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  ProductVariant|null  $variant
     * @param  string|null  $timezone
     * @return Collection  Collection of dates with availability status
     */
    public function getAvailableDates(
        Product $product,
        Carbon $startDate,
        Carbon $endDate,
        ?ProductVariant $variant = null,
        ?string $timezone = null
    ): Collection {
        $productTimezone = $this->getProductTimezone($product);
        $startDateInTimezone = $this->convertToTimezone($startDate, $productTimezone, $timezone);
        $endDateInTimezone = $this->convertToTimezone($endDate, $productTimezone, $timezone);

        $period = CarbonPeriod::create($startDateInTimezone, $endDateInTimezone);
        $dates = collect();

        foreach ($period as $date) {
            $availability = $this->checkDateAvailability($product, $date, 1, $variant, $timezone);
            
            $dates->push([
                'date' => $date->toDateString(),
                'available' => $availability['available'],
                'available_quantity' => $availability['available_quantity'] ?? 0,
                'status' => $this->getDateStatus($product, $variant, $date, $availability),
                'reason' => $availability['reason'] ?? null,
            ]);
        }

        return $dates;
    }

    /**
     * Get date status (available, unavailable, partially-booked).
     */
    protected function getDateStatus(
        Product $product,
        ?ProductVariant $variant,
        Carbon $date,
        array $availability
    ): string {
        if (!$availability['available']) {
            return 'unavailable';
        }

        $bookedQuantity = $this->getBookedQuantity($product, $variant, $date);
        $maxQuantity = $availability['max_quantity'] ?? PHP_INT_MAX;
        $availableQuantity = $availability['available_quantity'] ?? 0;

        if ($availableQuantity === $maxQuantity) {
            return 'available';
        }

        if ($availableQuantity > 0) {
            return 'partially-booked';
        }

        return 'unavailable';
    }

    /**
     * Reserve a date (create booking).
     *
     * @param  Product  $product
     * @param  array  $bookingData
     * @return AvailabilityBooking
     */
    public function reserveDate(Product $product, array $bookingData): AvailabilityBooking
    {
        return DB::transaction(function () use ($product, $bookingData) {
            $variant = $bookingData['variant_id'] 
                ? ProductVariant::find($bookingData['variant_id']) 
                : null;

            $startDate = Carbon::parse($bookingData['start_date']);
            $endDate = isset($bookingData['end_date']) 
                ? Carbon::parse($bookingData['end_date']) 
                : null;
            $quantity = $bookingData['quantity'] ?? 1;

            // Validate availability
            $availability = $this->checkDateAvailability(
                $product,
                $startDate,
                $quantity,
                $variant,
                $bookingData['timezone'] ?? null
            );

            if (!$availability['available']) {
                throw new \Exception('Date is not available: ' . $availability['reason']);
            }

            // Check if end date is provided and validate it
            if ($endDate) {
                $endDateAvailability = $this->checkDateAvailability(
                    $product,
                    $endDate,
                    $quantity,
                    $variant,
                    $bookingData['timezone'] ?? null
                );

                if (!$endDateAvailability['available']) {
                    throw new \Exception('End date is not available: ' . $endDateAvailability['reason']);
                }

                // Check all dates in range
                $period = CarbonPeriod::create($startDate, $endDate);
                foreach ($period as $date) {
                    $dateAvailability = $this->checkDateAvailability(
                        $product,
                        $date,
                        $quantity,
                        $variant,
                        $bookingData['timezone'] ?? null
                    );

                    if (!$dateAvailability['available']) {
                        throw new \Exception("Date {$date->toDateString()} is not available");
                    }
                }
            }

            // Calculate pricing
            $pricing = $this->calculateRentalPricing($product, $variant, $startDate, $endDate, $quantity);

            // Create booking
            $booking = AvailabilityBooking::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'customer_id' => $bookingData['customer_id'] ?? null,
                'order_id' => $bookingData['order_id'] ?? null,
                'order_line_id' => $bookingData['order_line_id'] ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => isset($bookingData['start_time']) ? Carbon::parse($bookingData['start_time']) : null,
                'end_time' => isset($bookingData['end_time']) ? Carbon::parse($bookingData['end_time']) : null,
                'quantity' => $quantity,
                'status' => $bookingData['status'] ?? 'pending',
                'total_price' => $pricing['total_price'],
                'currency_code' => $pricing['currency_code'],
                'duration_days' => $pricing['duration_days'],
                'pricing_type' => $pricing['pricing_type'],
                'customer_name' => $bookingData['customer_name'] ?? null,
                'customer_email' => $bookingData['customer_email'] ?? null,
                'customer_phone' => $bookingData['customer_phone'] ?? null,
                'notes' => $bookingData['notes'] ?? null,
                'timezone' => $bookingData['timezone'] ?? $this->getProductTimezone($product),
            ]);

            // Send confirmation notification
            $this->sendBookingNotification($booking, 'booking_confirmed');

            return $booking;
        });
    }

    /**
     * Calculate rental pricing.
     *
     * @param  Product  $product
     * @param  ProductVariant|null  $variant
     * @param  Carbon  $startDate
     * @param  Carbon|null  $endDate
     * @param  int  $quantity
     * @return array
     */
    public function calculateRentalPricing(
        Product $product,
        ?ProductVariant $variant,
        Carbon $startDate,
        ?Carbon $endDate,
        int $quantity = 1
    ): array {
        $durationDays = $endDate 
            ? $startDate->diffInDays($endDate) + 1 
            : 1;

        // Get base price
        $currency = \Lunar\Models\Currency::getDefault();
        $basePricing = \Lunar\Facades\Pricing::for($variant ?? $product->variants->first())
            ->currency($currency)
            ->get();
        $basePrice = $basePricing->matched?->price?->value ?? 0;

        // Determine pricing type based on duration
        $pricingType = 'daily';
        if ($durationDays >= 30) {
            $pricingType = 'monthly';
        } elseif ($durationDays >= 7) {
            $pricingType = 'weekly';
        }

        // Calculate total price
        $dailyRate = $basePrice / 100; // Convert from cents
        $totalPrice = 0;

        switch ($pricingType) {
            case 'monthly':
                $months = ceil($durationDays / 30);
                $totalPrice = $dailyRate * 30 * $months * 0.8; // 20% discount for monthly
                break;

            case 'weekly':
                $weeks = ceil($durationDays / 7);
                $totalPrice = $dailyRate * 7 * $weeks * 0.9; // 10% discount for weekly
                break;

            case 'daily':
            default:
                $totalPrice = $dailyRate * $durationDays;
                break;
        }

        // Apply quantity multiplier
        $totalPrice *= $quantity;

        return [
            'total_price' => round($totalPrice, 2),
            'currency_code' => $currency->code ?? 'USD',
            'duration_days' => $durationDays,
            'pricing_type' => $pricingType,
            'base_price' => $basePrice,
            'daily_rate' => $dailyRate,
        ];
    }

    /**
     * Get booked quantity for a date.
     */
    protected function getBookedQuantity(
        Product $product,
        ?ProductVariant $variant,
        Carbon $date
    ): int {
        return AvailabilityBooking::where('product_id', $product->id)
            ->when($variant, function ($q) use ($variant) {
                $q->where('product_variant_id', $variant->id);
            })
            ->forDate($date)
            ->sum('quantity');
    }

    /**
     * Get product availability rules.
     */
    protected function getProductAvailability(
        Product $product,
        ?ProductVariant $variant
    ): Collection {
        return ProductAvailability::where('product_id', $product->id)
            ->where(function ($q) use ($variant) {
                $q->whereNull('product_variant_id')
                  ->when($variant, function ($subQ) use ($variant) {
                      $subQ->orWhere('product_variant_id', $variant->id);
                  });
            })
            ->active()
            ->get();
    }

    /**
     * Check business rules.
     */
    protected function checkBusinessRules(
        Product $product,
        Carbon $date,
        int $quantity
    ): array {
        $rules = AvailabilityRule::where('product_id', $product->id)
            ->active()
            ->get();

        foreach ($rules as $rule) {
            switch ($rule->rule_type) {
                case 'lead_time':
                    $leadTimeHours = $rule->getLeadTimeHours();
                    if ($leadTimeHours && $date->diffInHours(now()) < $leadTimeHours) {
                        return [
                            'valid' => false,
                            'reason' => "Booking requires {$leadTimeHours} hours lead time",
                        ];
                    }
                    break;

                case 'buffer_time':
                    // Check if there's a buffer time requirement
                    $bufferHours = $rule->getBufferHours();
                    if ($bufferHours) {
                        $recentBookings = AvailabilityBooking::where('product_id', $product->id)
                            ->where('end_date', '>=', $date->copy()->subHours($bufferHours))
                            ->where('end_date', '<=', $date)
                            ->active()
                            ->exists();

                        if ($recentBookings) {
                            return [
                                'valid' => false,
                                'reason' => "Buffer time of {$bufferHours} hours required between bookings",
                            ];
                        }
                    }
                    break;
            }
        }

        return ['valid' => true];
    }

    /**
     * Get product timezone.
     */
    protected function getProductTimezone(Product $product): string
    {
        // Get from availability rules or default
        $availability = ProductAvailability::where('product_id', $product->id)
            ->whereNotNull('timezone')
            ->first();

        return $availability?->timezone ?? config('app.timezone', 'UTC');
    }

    /**
     * Convert date to timezone.
     */
    protected function convertToTimezone(
        Carbon $date,
        string $targetTimezone,
        ?string $sourceTimezone = null
    ): Carbon {
        if ($sourceTimezone) {
            return $date->setTimezone($sourceTimezone)->setTimezone($targetTimezone);
        }

        return $date->setTimezone($targetTimezone);
    }

    /**
     * Send booking notification.
     */
    protected function sendBookingNotification(
        AvailabilityBooking $booking,
        string $type
    ): void {
        AvailabilityNotification::create([
            'product_id' => $booking->product_id,
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'notification_type' => $type,
            'message' => $this->getNotificationMessage($booking, $type),
            'email' => $booking->customer_email,
            'is_sent' => false,
        ]);

        // Queue email notification
        // \App\Jobs\SendAvailabilityNotification::dispatch($notification);
    }

    /**
     * Get notification message.
     */
    protected function getNotificationMessage(AvailabilityBooking $booking, string $type): string
    {
        return match ($type) {
            'booking_confirmed' => "Your booking for {$booking->product->translateAttribute('name')} on {$booking->start_date->format('Y-m-d')} has been confirmed.",
            'booking_cancelled' => "Your booking for {$booking->product->translateAttribute('name')} on {$booking->start_date->format('Y-m-d')} has been cancelled.",
            'availability_changed' => "Availability for {$booking->product->translateAttribute('name')} has changed.",
            default => 'Notification',
        };
    }

    /**
     * Notify affected bookings of availability changes.
     */
    public function notifyAvailabilityChanges(Product $product, array $changes): void
    {
        $bookings = AvailabilityBooking::where('product_id', $product->id)
            ->active()
            ->get();

        foreach ($bookings as $booking) {
            AvailabilityNotification::create([
                'product_id' => $product->id,
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'notification_type' => 'availability_changed',
                'message' => 'Availability for your booking has changed.',
                'metadata' => $changes,
                'email' => $booking->customer_email,
                'is_sent' => false,
            ]);
        }
    }
}


