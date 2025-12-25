<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBadge;
use App\Models\ProductBadgeAssignment;
use App\Models\ProductBadgeRule;
use App\Models\ProductBadgePerformance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BadgeService
{
    /**
     * Assign a badge to a product.
     *
     * @param  Product  $product
     * @param  ProductBadge  $badge
     * @param  array  $options
     * @return ProductBadgeAssignment
     */
    public function assignBadge(Product $product, ProductBadge $badge, array $options = []): ProductBadgeAssignment
    {
        return DB::transaction(function () use ($product, $badge, $options) {
            // Check if assignment already exists
            $existing = ProductBadgeAssignment::where('product_id', $product->id)
                ->where('badge_id', $badge->id)
                ->first();

            if ($existing) {
                // Update existing assignment
                $existing->update([
                    'assignment_type' => $options['assignment_type'] ?? 'manual',
                    'priority' => $options['priority'] ?? $badge->priority,
                    'display_position' => $options['display_position'] ?? $badge->position,
                    'visibility_rules' => $options['visibility_rules'] ?? $badge->display_conditions,
                    'starts_at' => $options['starts_at'] ?? null,
                    'expires_at' => $options['expires_at'] ?? null,
                    'is_active' => $options['is_active'] ?? true,
                    'assigned_by' => auth()->id(),
                ]);

                return $existing;
            }

            // Create new assignment
            return ProductBadgeAssignment::create([
                'badge_id' => $badge->id,
                'product_id' => $product->id,
                'assignment_type' => $options['assignment_type'] ?? 'manual',
                'rule_id' => $options['rule_id'] ?? null,
                'priority' => $options['priority'] ?? $badge->priority,
                'display_position' => $options['display_position'] ?? $badge->position,
                'visibility_rules' => $options['visibility_rules'] ?? $badge->display_conditions,
                'starts_at' => $options['starts_at'] ?? null,
                'expires_at' => $options['expires_at'] ?? null,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
                'is_active' => $options['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Remove a badge from a product.
     *
     * @param  Product  $product
     * @param  ProductBadge  $badge
     * @param  bool  $force  Force remove even if automatic
     * @return bool
     */
    public function removeBadge(Product $product, ProductBadge $badge, bool $force = false): bool
    {
        $assignment = ProductBadgeAssignment::where('product_id', $product->id)
            ->where('badge_id', $badge->id)
            ->first();

        if (!$assignment) {
            return false;
        }

        // Don't remove automatic assignments unless forced
        if ($assignment->assignment_type === 'automatic' && !$force) {
            // Deactivate instead of deleting
            $assignment->update(['is_active' => false]);
            return true;
        }

        return $assignment->delete();
    }

    /**
     * Get all badges for a product.
     *
     * @param  Product  $product
     * @param  string|null  $context  Context for visibility check (category, product, search)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductBadges(Product $product, ?string $context = null)
    {
        $assignments = ProductBadgeAssignment::where('product_id', $product->id)
            ->active()
            ->with('badge')
            ->get()
            ->filter(function ($assignment) use ($context) {
                // Check if badge is active
                if (!$assignment->badge || !$assignment->badge->isActive()) {
                    return false;
                }

                // Check visibility rules if context provided
                if ($context && !$assignment->isVisibleIn($context)) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(function ($assignment) {
                // Sort by priority (higher first), then by assigned_at
                return [
                    $assignment->priority ?? $assignment->badge->priority ?? 0,
                    $assignment->assigned_at->timestamp ?? 0,
                ];
            })
            ->values();

        return $assignments;
    }

    /**
     * Evaluate and assign automatic badges based on rules.
     *
     * @param  Product|null  $product  If null, evaluate all products
     * @return int  Number of badges assigned
     */
    public function evaluateAutomaticBadges(?Product $product = null): int
    {
        $assigned = 0;

        // Get all active automatic rules
        $rules = ProductBadgeRule::active()
            ->automatic()
            ->with('badge')
            ->orderBy('priority', 'desc')
            ->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        // Get products to evaluate
        $products = $product 
            ? collect([$product])
            : Product::where('status', 'published')->get();

        foreach ($products as $product) {
            foreach ($rules as $rule) {
                if ($this->evaluateRule($product, $rule)) {
                    // Check if badge is already assigned
                    $existing = ProductBadgeAssignment::where('product_id', $product->id)
                        ->where('badge_id', $rule->badge_id)
                        ->where('assignment_type', 'automatic')
                        ->first();

                    if (!$existing) {
                        $this->assignBadge($product, $rule->badge, [
                            'assignment_type' => 'automatic',
                            'rule_id' => $rule->id,
                            'priority' => $rule->badge->priority,
                        ]);
                        $assigned++;
                    } elseif (!$existing->is_active) {
                        // Reactivate existing assignment
                        $existing->update(['is_active' => true]);
                        $assigned++;
                    }
                } else {
                    // Rule no longer matches, remove automatic assignment
                    $existing = ProductBadgeAssignment::where('product_id', $product->id)
                        ->where('badge_id', $rule->badge_id)
                        ->where('assignment_type', 'automatic')
                        ->where('rule_id', $rule->id)
                        ->first();

                    if ($existing) {
                        $existing->delete();
                    }
                }
            }
        }

        return $assigned;
    }

    /**
     * Evaluate if a product matches a rule's conditions.
     *
     * @param  Product  $product
     * @param  ProductBadgeRule  $rule
     * @return bool
     */
    protected function evaluateRule(Product $product, ProductBadgeRule $rule): bool
    {
        $conditions = $rule->conditions ?? [];

        if (empty($conditions)) {
            return false;
        }

        // Check each condition
        foreach ($conditions as $conditionType => $conditionConfig) {
            if (!isset($conditionConfig['enabled']) || !$conditionConfig['enabled']) {
                continue;
            }

            $matches = match ($conditionType) {
                'is_new' => $this->checkIsNew($product, $conditionConfig),
                'on_sale' => $this->checkOnSale($product, $conditionConfig),
                'low_stock' => $this->checkLowStock($product, $conditionConfig),
                'best_seller' => $this->checkBestSeller($product, $conditionConfig),
                'featured' => $this->checkFeatured($product, $conditionConfig),
                'price_range' => $this->checkPriceRange($product, $conditionConfig),
                'category' => $this->checkCategory($product, $conditionConfig),
                'tag' => $this->checkTag($product, $conditionConfig),
                'custom_field' => $this->checkCustomField($product, $conditionConfig),
                default => false,
            };

            // If any condition matches, the rule matches (OR logic)
            // For AND logic, we'd need to check all conditions
            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if product is new.
     */
    protected function checkIsNew(Product $product, array $config): bool
    {
        $days = $config['days'] ?? 30;
        $createdAt = $product->created_at ?? $product->published_at;

        return $createdAt && $createdAt->isAfter(now()->subDays($days));
    }

    /**
     * Check if product is on sale.
     */
    protected function checkOnSale(Product $product, array $config): bool
    {
        // Check if product has compare_at_price set on any variant
        foreach ($product->variants as $variant) {
            $prices = $variant->prices;
            foreach ($prices as $price) {
                if ($price->compare_price && $price->compare_price > $price->price) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if product has low stock.
     */
    protected function checkLowStock(Product $product, array $config): bool
    {
        $threshold = $config['threshold'] ?? 10;

        foreach ($product->variants as $variant) {
            $stock = $variant->stock ?? 0;
            if ($stock > 0 && $stock <= $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if product is a best seller.
     */
    protected function checkBestSeller(Product $product, array $config): bool
    {
        $threshold = $config['sales_threshold'] ?? 100;
        $period = $config['period'] ?? 30; // days

        // This would require tracking sales - for now, we'll use a placeholder
        // In a real implementation, you'd query order items for this product
        $salesCount = $this->getProductSalesCount($product, $period);

        return $salesCount >= $threshold;
    }

    /**
     * Check if product is featured.
     */
    protected function checkFeatured(Product $product, array $config): bool
    {
        // Check if product has a 'featured' custom field or attribute
        $customMeta = $product->custom_meta ?? [];
        return isset($customMeta['featured']) && $customMeta['featured'] === true;
    }

    /**
     * Check if product price is in range.
     */
    protected function checkPriceRange(Product $product, array $config): bool
    {
        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? PHP_INT_MAX;

        foreach ($product->variants as $variant) {
            $prices = $variant->prices;
            foreach ($prices as $price) {
                $priceValue = $price->price ?? 0;
                if ($priceValue >= $min && $priceValue <= $max) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if product is in specified categories.
     */
    protected function checkCategory(Product $product, array $config): bool
    {
        $categoryIds = $config['category_ids'] ?? [];

        if (empty($categoryIds)) {
            return false;
        }

        $productCategoryIds = $product->collections()->pluck('id')->toArray();

        return !empty(array_intersect($categoryIds, $productCategoryIds));
    }

    /**
     * Check if product has specified tags.
     */
    protected function checkTag(Product $product, array $config): bool
    {
        $tagIds = $config['tag_ids'] ?? [];

        if (empty($tagIds)) {
            return false;
        }

        // Assuming products have a tags relationship
        if (method_exists($product, 'tags')) {
            $productTagIds = $product->tags()->pluck('id')->toArray();
            return !empty(array_intersect($tagIds, $productTagIds));
        }

        return false;
    }

    /**
     * Check if product has custom field value.
     */
    protected function checkCustomField(Product $product, array $config): bool
    {
        $field = $config['field'] ?? null;
        $value = $config['value'] ?? null;

        if (!$field) {
            return false;
        }

        $customMeta = $product->custom_meta ?? [];

        if (!isset($customMeta[$field])) {
            return false;
        }

        if ($value !== null) {
            return $customMeta[$field] == $value;
        }

        return !empty($customMeta[$field]);
    }

    /**
     * Get product sales count for a period.
     *
     * @param  Product  $product
     * @param  int  $days
     * @return int
     */
    protected function getProductSalesCount(Product $product, int $days): int
    {
        // This is a placeholder - in a real implementation, you'd query order items
        // For now, return 0 or use a cached value
        return 0;
    }

    /**
     * Track badge performance metrics.
     *
     * @param  Product  $product
     * @param  ProductBadge  $badge
     * @param  string  $metric  views, clicks, add_to_cart, purchases
     * @param  float|null  $revenue
     * @return void
     */
    public function trackPerformance(Product $product, ProductBadge $badge, string $metric, ?float $revenue = null): void
    {
        $today = now()->toDateString();

        $performance = ProductBadgePerformance::firstOrCreate(
            [
                'badge_id' => $badge->id,
                'product_id' => $product->id,
                'period_start' => $today,
                'period_end' => $today,
            ],
            [
                'views' => 0,
                'clicks' => 0,
                'add_to_cart' => 0,
                'purchases' => 0,
                'revenue' => 0,
            ]
        );

        $performance->incrementMetric($metric, 1);

        if ($revenue !== null && $metric === 'purchases') {
            $performance->increment('revenue', $revenue);
        }
    }

    /**
     * Get badge performance summary.
     *
     * @param  ProductBadge  $badge
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function getBadgePerformance(ProductBadge $badge, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ProductBadgePerformance::where('badge_id', $badge->id);

        if ($startDate) {
            $query->where('period_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('period_end', '<=', $endDate);
        }

        $result = $query->selectRaw('
                SUM(views) as total_views,
                SUM(clicks) as total_clicks,
                SUM(add_to_cart) as total_add_to_cart,
                SUM(purchases) as total_purchases,
                SUM(revenue) as total_revenue,
                AVG(click_through_rate) as avg_ctr,
                AVG(conversion_rate) as avg_cr,
                AVG(add_to_cart_rate) as avg_atc_rate
            ')
            ->first();

        return $result ? $result->toArray() : [
            'total_views' => 0,
            'total_clicks' => 0,
            'total_add_to_cart' => 0,
            'total_purchases' => 0,
            'total_revenue' => 0,
            'avg_ctr' => 0,
            'avg_cr' => 0,
            'avg_atc_rate' => 0,
        ];
    }
}

