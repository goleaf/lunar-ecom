<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Models\ProductVariant;

/**
 * Service for managing product scheduling.
 */
class ProductSchedulingService
{
    /**
     * Create a product schedule.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return ProductSchedule
     */
    public function createSchedule(Product $product, array $data): ProductSchedule
    {
        return ProductSchedule::create([
            'product_id' => $product->id,
            'type' => $data['type'] ?? 'publish',
            'schedule_type' => $data['schedule_type'] ?? 'one_time',
            'scheduled_at' => $data['scheduled_at'],
            'expires_at' => $data['expires_at'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'days_of_week' => $data['days_of_week'] ?? null,
            'time_start' => $data['time_start'] ?? null,
            'time_end' => $data['time_end'] ?? null,
            'timezone' => $data['timezone'] ?? config('app.timezone', 'UTC'),
            'target_status' => $data['target_status'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sale_price' => $data['sale_price'] ?? null,
            'sale_percentage' => $data['sale_percentage'] ?? null,
            'restore_original_price' => $data['restore_original_price'] ?? true,
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_pattern' => $data['recurrence_pattern'] ?? null,
            'recurrence_config' => $data['recurrence_config'] ?? null,
            'send_notification' => $data['send_notification'] ?? false,
            'notification_hours_before' => $data['notification_hours_before'] ?? null,
            'season_tag' => $data['season_tag'] ?? null,
            'auto_unpublish_after_season' => $data['auto_unpublish_after_season'] ?? false,
            'is_coming_soon' => $data['is_coming_soon'] ?? false,
            'coming_soon_message' => $data['coming_soon_message'] ?? null,
            'bulk_schedule_id' => $data['bulk_schedule_id'] ?? null,
            'applied_to' => $data['applied_to'] ?? null,
        ]);
    }

    /**
     * Execute due schedules.
     *
     * @return int Number of schedules executed
     */
    public function executeDueSchedules(): int
    {
        $schedules = ProductSchedule::due()->with('product')->get();
        $executed = 0;

        foreach ($schedules as $schedule) {
            try {
                $this->executeSchedule($schedule);
                $executed++;
            } catch (\Exception $e) {
                Log::error('Failed to execute product schedule', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);

                $schedule->update([
                    'execution_success' => false,
                    'execution_error' => $e->getMessage(),
                    'executed_at' => now(),
                ]);
            }
        }

        return $executed;
    }

    /**
     * Execute a single schedule.
     *
     * @param  ProductSchedule  $schedule
     * @return void
     */
    public function executeSchedule(ProductSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $product = $schedule->product;

            if (!$product) {
                throw new \Exception('Product not found for schedule');
            }

            // Check time-based availability for recurring schedules
            if ($schedule->schedule_type === 'recurring' && $schedule->days_of_week) {
                $timezone = $schedule->timezone ?? config('app.timezone', 'UTC');
                $now = now()->setTimezone($timezone);
                $currentDay = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday
                
                if (!in_array($currentDay, $schedule->days_of_week)) {
                    return; // Not the right day of week
                }

                // Check time range
                if ($schedule->time_start && $schedule->time_end) {
                    $currentTime = $now->format('H:i:s');
                    if ($currentTime < $schedule->time_start || $currentTime > $schedule->time_end) {
                        return; // Outside time range
                    }
                }
            }

            $previousStatus = $product->status;

            switch ($schedule->type) {
                case 'publish':
                    $this->publishProduct($product, $schedule);
                    break;

                case 'unpublish':
                    $this->unpublishProduct($product, $schedule);
                    break;

                case 'flash_sale':
                    $this->startFlashSale($product, $schedule);
                    break;

                case 'seasonal':
                    $this->publishProduct($product, $schedule);
                    break;

                case 'time_limited':
                    $this->publishProduct($product, $schedule);
                    break;
            }

            // Record history
            $this->recordHistory(
                $schedule,
                $schedule->type,
                $previousStatus,
                $product->fresh()->status,
                ['schedule_id' => $schedule->id, 'timezone' => $schedule->timezone]
            );

            // Mark as executed (only for one-time schedules)
            if ($schedule->schedule_type === 'one_time') {
                $schedule->update([
                    'execution_success' => true,
                    'executed_at' => now(),
                ]);
            }

            // Handle recurring schedules
            if ($schedule->is_recurring && $schedule->schedule_type === 'recurring') {
                // Don't create next recurrence here - it's handled by the recurring logic
            }

            // Send notification if enabled
            if ($schedule->send_notification && !$schedule->notification_sent_at) {
                $this->sendNotification($schedule);
            }
        });
    }

    /**
     * Publish a product.
     *
     * @param  Product  $product
     * @param  ProductSchedule  $schedule
     * @return void
     */
    protected function publishProduct(Product $product, ProductSchedule $schedule): void
    {
        $status = $schedule->target_status ?? 'published';
        $product->update([
            'status' => $status,
            'is_coming_soon' => false, // Remove coming soon status when published
        ]);

        // Set published_at if not set
        if (!$product->published_at) {
            $product->update(['published_at' => now()]);
        }

        // Notify coming soon subscribers
        if ($schedule->is_coming_soon) {
            $this->notifyComingSoonSubscribers($product);
        }
    }

    /**
     * Unpublish a product.
     *
     * @param  Product  $product
     * @param  ProductSchedule  $schedule
     * @return void
     */
    protected function unpublishProduct(Product $product, ProductSchedule $schedule): void
    {
        $status = $schedule->target_status ?? 'draft';
        $product->update(['status' => $status]);
    }

    /**
     * Start a flash sale.
     *
     * @param  Product  $product
     * @param  ProductSchedule  $schedule
     * @return void
     */
    protected function startFlashSale(Product $product, ProductSchedule $schedule): void
    {
        // Publish product if not already published
        if ($product->status !== 'published') {
            $product->update(['status' => 'published']);
        }

        // Apply sale pricing to all variants
        foreach ($product->variants as $variant) {
            $this->applySalePrice($variant, $schedule);
        }
    }

    /**
     * Apply sale price to variant.
     *
     * @param  ProductVariant  $variant
     * @param  ProductSchedule  $schedule
     * @return void
     */
    protected function applySalePrice(ProductVariant $variant, ProductSchedule $schedule): void
    {
        $currency = \Lunar\Facades\Currency::getDefault();
        $pricing = \Lunar\Facades\Pricing::for($variant)->currency($currency)->get();
        
        if (!$pricing->matched?->price) {
            return;
        }

        $originalPrice = $pricing->matched->price->value;
        
        // Calculate sale price
        $salePrice = $originalPrice;
        
        if ($schedule->sale_price) {
            $salePrice = (int)($schedule->sale_price * 100); // Convert to cents
        } elseif ($schedule->sale_percentage) {
            $discount = ($originalPrice * $schedule->sale_percentage) / 100;
            $salePrice = $originalPrice - $discount;
        }

        // Store original price in custom meta if needed
        if ($schedule->restore_original_price) {
            $variant->update([
                'custom_meta' => array_merge(
                    $variant->custom_meta ?? [],
                    ['original_price' => $originalPrice, 'sale_schedule_id' => $schedule->id]
                ),
            ]);
        }

        // Update price
        $variant->prices()->updateOrCreate(
            [
                'currency_id' => $currency->id,
                'price_type' => 'default',
            ],
            [
                'price' => (int)$salePrice,
            ]
        );
    }

    /**
     * End flash sale and restore original prices.
     *
     * @param  ProductSchedule  $schedule
     * @return void
     */
    public function endFlashSale(ProductSchedule $schedule): void
    {
        if (!$schedule->isFlashSale() || !$schedule->restore_original_price) {
            return;
        }

        $product = $schedule->product;
        $currency = \Lunar\Facades\Currency::getDefault();

        foreach ($product->variants as $variant) {
            $customMeta = $variant->custom_meta ?? [];
            
            if (isset($customMeta['original_price']) && $customMeta['sale_schedule_id'] == $schedule->id) {
                $originalPrice = $customMeta['original_price'];
                
                // Restore original price
                $variant->prices()->updateOrCreate(
                    [
                        'currency_id' => $currency->id,
                        'price_type' => 'default',
                    ],
                    [
                        'price' => $originalPrice,
                    ]
                );

                // Remove sale metadata
                unset($customMeta['original_price'], $customMeta['sale_schedule_id']);
                $variant->update(['custom_meta' => $customMeta]);
            }
        }
    }

    /**
     * Handle expired schedules.
     *
     * @return int Number of schedules handled
     */
    public function handleExpiredSchedules(): int
    {
        $schedules = ProductSchedule::expired()
            ->whereNull('executed_at')
            ->with('product')
            ->get();

        $handled = 0;

        foreach ($schedules as $schedule) {
            try {
                if ($schedule->isFlashSale()) {
                    $this->endFlashSale($schedule);
                } elseif ($schedule->type === 'time_limited') {
                    $this->unpublishProduct($schedule->product, $schedule);
                }

                $schedule->update([
                    'executed_at' => now(),
                    'execution_success' => true,
                ]);

                $handled++;
            } catch (\Exception $e) {
                Log::error('Failed to handle expired schedule', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $handled;
    }

    /**
     * Create next recurrence for recurring schedule.
     *
     * @param  ProductSchedule  $schedule
     * @return ProductSchedule|null
     */
    protected function createNextRecurrence(ProductSchedule $schedule): ?ProductSchedule
    {
        $nextDate = $this->calculateNextRecurrence($schedule);

        if (!$nextDate) {
            return null;
        }

        return ProductSchedule::create([
            'product_id' => $schedule->product_id,
            'type' => $schedule->type,
            'scheduled_at' => $nextDate,
            'expires_at' => $schedule->expires_at ? $nextDate->copy()->add($schedule->scheduled_at->diff($schedule->expires_at)) : null,
            'target_status' => $schedule->target_status,
            'is_active' => $schedule->is_active,
            'sale_price' => $schedule->sale_price,
            'sale_percentage' => $schedule->sale_percentage,
            'restore_original_price' => $schedule->restore_original_price,
            'is_recurring' => $schedule->is_recurring,
            'recurrence_pattern' => $schedule->recurrence_pattern,
            'recurrence_config' => $schedule->recurrence_config,
            'send_notification' => $schedule->send_notification,
        ]);
    }

    /**
     * Calculate next recurrence date.
     *
     * @param  ProductSchedule  $schedule
     * @return \Carbon\Carbon|null
     */
    protected function calculateNextRecurrence(ProductSchedule $schedule): ?\Carbon\Carbon
    {
        if (!$schedule->is_recurring || !$schedule->recurrence_pattern) {
            return null;
        }

        $nextDate = $schedule->scheduled_at->copy();

        return match ($schedule->recurrence_pattern) {
            'daily' => $nextDate->addDay(),
            'weekly' => $nextDate->addWeek(),
            'monthly' => $nextDate->addMonth(),
            'yearly' => $nextDate->addYear(),
            default => null,
        };
    }

    /**
     * Send notification for schedule.
     *
     * @param  ProductSchedule  $schedule
     * @return void
     */
    protected function sendNotification(ProductSchedule $schedule): void
    {
        // Send admin notification before schedule execution
        if ($schedule->send_notification && !$schedule->notification_sent_at) {
            // Dispatch notification job
            \App\Jobs\SendScheduleNotification::dispatch($schedule);
            $schedule->update(['notification_sent_at' => now()]);
        }
    }

    /**
     * Send scheduled notifications (for notifications scheduled hours before).
     *
     * @return int Number of notifications sent
     */
    public function sendScheduledNotifications(): int
    {
        $sent = 0;

        $schedules = ProductSchedule::where('send_notification', true)
            ->whereNull('notification_sent_at')
            ->whereNotNull('notification_hours_before')
            ->whereNotNull('scheduled_at')
            ->get();

        foreach ($schedules as $schedule) {
            $notificationTime = $schedule->scheduled_at->copy()
                ->subHours($schedule->notification_hours_before);

            if ($notificationTime->lte(now()) && !$schedule->notification_sent_at) {
                $this->sendNotification($schedule);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Notify coming soon subscribers when product becomes available.
     *
     * @param  Product  $product
     * @return void
     */
    protected function notifyComingSoonSubscribers(Product $product): void
    {
        $notifications = \App\Models\ComingSoonNotification::where('product_id', $product->id)
            ->where('notified', false)
            ->get();

        foreach ($notifications as $notification) {
            try {
                \App\Jobs\SendComingSoonAvailableNotification::dispatch($notification);
                $notification->update(['notified' => true, 'notified_at' => now()]);
            } catch (\Exception $e) {
                Log::error("Failed to notify coming soon subscriber {$notification->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get upcoming schedules for a product.
     *
     * @param  Product  $product
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUpcomingSchedules(Product $product)
    {
        return ProductSchedule::where('product_id', $product->id)
            ->upcoming()
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Get active schedules for a product.
     *
     * @param  Product  $product
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSchedules(Product $product)
    {
        return ProductSchedule::where('product_id', $product->id)
            ->active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Check product availability based on schedules.
     *
     * @param  Product  $product
     * @param  string|null  $timezone
     * @return array
     */
    public function checkAvailability(Product $product, ?string $timezone = null): array
    {
        $timezone = $timezone ?? config('app.timezone', 'UTC');
        $now = now()->setTimezone($timezone);

        // Check if product is coming soon
        if ($product->isComingSoon()) {
            return [
                'available' => false,
                'status' => 'coming_soon',
                'message' => $product->coming_soon_message ?? 'This product is coming soon',
                'expected_available_at' => $product->expected_available_at,
            ];
        }

        // Check active schedules
        $activeSchedules = $this->getActiveSchedules($product);
        $flashSaleSchedule = $activeSchedules->firstWhere('type', 'flash_sale');

        // Check if product is in a flash sale
        if ($flashSaleSchedule && $flashSaleSchedule->isDue() && !$flashSaleSchedule->isExpired()) {
            return [
                'available' => true,
                'status' => 'flash_sale',
                'flash_sale' => true,
                'flash_sale_ends_at' => $flashSaleSchedule->expires_at,
                'sale_price' => $flashSaleSchedule->sale_price,
                'sale_percentage' => $flashSaleSchedule->sale_percentage,
            ];
        }

        // Check if product is scheduled for publish/unpublish
        if ($product->isScheduledForPublish()) {
            return [
                'available' => false,
                'status' => 'scheduled',
                'message' => 'This product will be available soon',
                'available_at' => $product->scheduled_publish_at,
            ];
        }

        if ($product->isScheduledForUnpublish()) {
            return [
                'available' => true,
                'status' => 'available_until',
                'message' => 'This product will be unavailable soon',
                'unavailable_at' => $product->scheduled_unpublish_at,
            ];
        }

        // Default: check product status
        return [
            'available' => $product->status === 'published',
            'status' => $product->status,
        ];
    }

    /**
     * Schedule product publication with timezone support.
     *
     * @param  Product  $product
     * @param  \DateTimeInterface|string  $publishAt
     * @param  string|null  $timezone
     * @return ProductSchedule
     */
    public function schedulePublication(Product $product, $publishAt, ?string $timezone = null): ProductSchedule
    {
        $timezone = $timezone ?? config('app.timezone', 'UTC');
        $publishDateTime = $publishAt instanceof \DateTimeInterface 
            ? \Carbon\Carbon::instance($publishAt)->setTimezone($timezone)
            : \Carbon\Carbon::parse($publishAt, $timezone);

        return $this->createSchedule($product, [
            'type' => 'publish',
            'schedule_type' => 'one_time',
            'scheduled_at' => $publishDateTime,
            'target_status' => 'published',
            'timezone' => $timezone,
        ]);
    }

    /**
     * Schedule product unpublication with timezone support.
     *
     * @param  Product  $product
     * @param  \DateTimeInterface|string  $unpublishAt
     * @param  string|null  $timezone
     * @return ProductSchedule
     */
    public function scheduleUnpublication(Product $product, $unpublishAt, ?string $timezone = null): ProductSchedule
    {
        $timezone = $timezone ?? config('app.timezone', 'UTC');
        $unpublishDateTime = $unpublishAt instanceof \DateTimeInterface 
            ? \Carbon\Carbon::instance($unpublishAt)->setTimezone($timezone)
            : \Carbon\Carbon::parse($unpublishAt, $timezone);

        return $this->createSchedule($product, [
            'type' => 'unpublish',
            'schedule_type' => 'one_time',
            'scheduled_at' => $unpublishDateTime,
            'target_status' => 'draft',
            'timezone' => $timezone,
        ]);
    }

    /**
     * Record schedule history.
     *
     * @param  ProductSchedule  $schedule
     * @param  string  $action
     * @param  string|null  $previousStatus
     * @param  string|null  $newStatus
     * @param  array|null  $metadata
     * @return void
     */
    public function recordHistory(
        ProductSchedule $schedule,
        string $action,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?array $metadata = null
    ): void {
        \App\Models\ProductScheduleHistory::create([
            'product_schedule_id' => $schedule->id,
            'product_id' => $schedule->product_id,
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'metadata' => $metadata,
            'executed_at' => now(),
            'timezone' => $schedule->timezone ?? config('app.timezone', 'UTC'),
        ]);
    }
}

