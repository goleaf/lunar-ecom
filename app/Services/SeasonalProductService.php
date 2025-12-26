<?php

namespace App\Services;

use App\Models\ProductSchedule;
use App\Models\SeasonalProductRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

/**
 * Service for managing seasonal product automation.
 */
class SeasonalProductService
{
    /**
     * Process seasonal products based on active rules.
     *
     * @return int Number of products processed
     */
    public function processSeasonalProducts(): int
    {
        $processed = 0;
        $now = now();

        // Get active seasonal rules
        $rules = SeasonalProductRule::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->where('start_date', '<=', $now)
                  ->where('end_date', '>=', $now);
            })
            ->get();

        foreach ($rules as $rule) {
            try {
                // Get products to apply rule to
                $products = $this->getProductsForRule($rule);

                foreach ($products as $product) {
                    // Check if already scheduled
                    $existingSchedule = ProductSchedule::where('product_id', $product->id)
                        ->where('season_tag', $rule->season_tag)
                        ->where('type', 'seasonal')
                        ->where('is_active', true)
                        ->first();

                    if ($existingSchedule) {
                        continue;
                    }

                    // Calculate publish date (days before start)
                    $publishDate = $rule->start_date->copy()->subDays($rule->days_before_start);
                    $unpublishDate = $rule->end_date->copy()->addDays($rule->days_after_end);

                    // Create schedule if auto-publish is enabled
                    if ($rule->auto_publish && $publishDate->lte($now) && $rule->end_date->gte($now)) {
                        $schedulingService = app(ProductSchedulingService::class);
                        $schedulingService->createSchedule($product, [
                            'type' => 'seasonal',
                            'schedule_type' => 'one_time',
                            'scheduled_at' => $publishDate->setTimezone($rule->timezone),
                            'expires_at' => $unpublishDate->setTimezone($rule->timezone),
                            'target_status' => Product::STATUS_ACTIVE,
                            'season_tag' => $rule->season_tag,
                            'auto_unpublish_after_season' => $rule->auto_unpublish,
                            'timezone' => $rule->timezone,
                        ]);

                        // Publish product if date has passed
                        if ($publishDate->lte($now)) {
                            $product->update(['status' => Product::STATUS_ACTIVE]);
                        }

                        $processed++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to process seasonal rule {$rule->id}: " . $e->getMessage());
            }
        }

        // Handle expired seasonal products
        $this->handleExpiredSeasonalProducts();

        return $processed;
    }

    /**
     * Get products for a seasonal rule.
     *
     * @param  SeasonalProductRule  $rule
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getProductsForRule(SeasonalProductRule $rule)
    {
        $query = Product::query();

        // Apply to specific products
        if (!empty($rule->applied_to_products)) {
            $query->whereIn('id', $rule->applied_to_products);
        }

        // Apply to categories
        if (!empty($rule->applied_to_categories)) {
            $query->orWhereHas('collections', function ($q) use ($rule) {
                $q->whereIn('id', $rule->applied_to_categories);
            });
        }

        // Apply to tags
        if (!empty($rule->applied_to_tags)) {
            $query->orWhereHas('tags', function ($q) use ($rule) {
                $q->whereIn('id', $rule->applied_to_tags);
            });
        }

        return $query->get();
    }

    /**
     * Handle expired seasonal products.
     *
     * @return void
     */
    protected function handleExpiredSeasonalProducts(): void
    {
        $now = now();

        // Find expired seasonal schedules
        $expiredSchedules = ProductSchedule::where('type', 'seasonal')
            ->where('auto_unpublish_after_season', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->where('is_active', true)
            ->whereNull('executed_at')
            ->with('product')
            ->get();

        foreach ($expiredSchedules as $schedule) {
            try {
                if ($schedule->product) {
                    $schedule->product->update(['status' => 'draft']);
                }

                $schedule->update([
                    'executed_at' => now(),
                    'execution_success' => true,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to unpublish seasonal product {$schedule->product_id}: " . $e->getMessage());
            }
        }
    }
}

