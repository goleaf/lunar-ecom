<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBadge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing product badges.
 */
class ProductBadgeService
{
    /**
     * Assign badge to product.
     *
     * @param  Product  $product
     * @param  ProductBadge  $badge
     * @param  array  $options
     * @return void
     */
    public function assignBadge(Product $product, ProductBadge $badge, array $options = []): void
    {
        // Check if already assigned
        if ($product->badges()->where('product_badges.id', $badge->id)->exists()) {
            return;
        }

        $product->badges()->attach($badge->id, [
            'is_auto_assigned' => $options['is_auto_assigned'] ?? false,
            'assigned_at' => now(),
            'expires_at' => $options['expires_at'] ?? null,
            'position' => $options['position'] ?? null,
            'priority' => $options['priority'] ?? null,
        ]);
    }

    /**
     * Remove badge from product.
     *
     * @param  Product  $product
     * @param  ProductBadge  $badge
     * @return void
     */
    public function removeBadge(Product $product, ProductBadge $badge): void
    {
        $product->badges()->detach($badge->id);
    }

    /**
     * Auto-assign badges to product based on rules.
     *
     * @param  Product  $product
     * @return array Array of assigned badge IDs
     */
    public function autoAssignBadges(Product $product): array
    {
        $assigned = [];
        $badges = ProductBadge::autoAssign()->get();

        foreach ($badges as $badge) {
            if ($this->matchesRules($product, $badge)) {
                $this->assignBadge($product, $badge, [
                    'is_auto_assigned' => true,
                ]);
                $assigned[] = $badge->id;
            }
        }

        return $assigned;
    }

    /**
     * Check if product matches badge assignment rules.
     *
     * @param  Product  $product
     * @param  ProductBadge  $badge
     * @return bool
     */
    protected function matchesRules(Product $product, ProductBadge $badge): bool
    {
        $rules = $badge->assignment_rules ?? [];

        if (empty($rules)) {
            return false;
        }

        // Check each rule condition
        foreach ($rules as $rule) {
            if (!$this->evaluateRule($product, $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single rule.
     *
     * @param  Product  $product
     * @param  array  $rule
     * @return bool
     */
    protected function evaluateRule(Product $product, array $rule): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? null;

        if (!$field) {
            return true;
        }

        $productValue = $this->getProductValue($product, $field);

        return match ($operator) {
            'equals' => $productValue == $value,
            'not_equals' => $productValue != $value,
            'greater_than' => $productValue > $value,
            'less_than' => $productValue < $value,
            'greater_or_equal' => $productValue >= $value,
            'less_or_equal' => $productValue <= $value,
            'contains' => str_contains((string)$productValue, (string)$value),
            'in' => in_array($productValue, (array)$value),
            'not_in' => !in_array($productValue, (array)$value),
            'is_null' => is_null($productValue),
            'is_not_null' => !is_null($productValue),
            default => false,
        };
    }

    /**
     * Get product value for field.
     *
     * @param  Product  $product
     * @param  string  $field
     * @return mixed
     */
    protected function getProductValue(Product $product, string $field)
    {
        // Handle nested fields
        if (str_contains($field, '.')) {
            [$relation, $attribute] = explode('.', $field, 2);
            
            return match ($relation) {
                'variant' => $product->variants->first()?->$attribute,
                'brand' => $product->brand?->$attribute,
                'category' => $product->categories->first()?->$attribute,
                default => null,
            };
        }

        // Handle special fields
        return match ($field) {
            'created_days_ago' => $product->created_at->diffInDays(now()),
            'updated_days_ago' => $product->updated_at->diffInDays(now()),
            'published_days_ago' => $product->published_at?->diffInDays(now()),
            'stock_total' => $product->variants->sum('stock'),
            'has_low_stock' => $product->variants->where('stock', '>', 0)->where('stock', '<=', 10)->count() > 0,
            'is_on_sale' => $this->isOnSale($product),
            'has_reviews' => $product->reviews()->count() > 0,
            'average_rating' => $product->average_rating ?? 0,
            default => $product->$field ?? null,
        };
    }

    /**
     * Check if product is on sale.
     *
     * @param  Product  $product
     * @return bool
     */
    protected function isOnSale(Product $product): bool
    {
        $variant = $product->variants->first();
        if (!$variant) {
            return false;
        }

        $pricing = \Lunar\Facades\Pricing::for($variant)->get();
        
        if ($pricing->matched?->compare_price && $pricing->matched->compare_price->value > $pricing->matched->price->value) {
            return true;
        }

        return false;
    }

    /**
     * Get badges for product.
     *
     * @param  Product  $product
     * @param  int|null  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductBadges(Product $product, ?int $limit = null)
    {
        $query = $product->badges()
            ->where(function ($q) {
                $q->wherePivotNull('expires_at')
                  ->orWherePivot('expires_at', '>', now());
            })
            ->active()
            ->orderByDesc('priority')
            ->orderByDesc('product_badge_product.priority');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Process all products for auto-assignment.
     *
     * @return int Number of products processed
     */
    public function processAutoAssignment(): int
    {
        $products = Product::published()->get();
        $processed = 0;

        foreach ($products as $product) {
            try {
                $this->autoAssignBadges($product);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to auto-assign badges for product', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Remove expired badge assignments.
     *
     * @return int Number of assignments removed
     */
    public function removeExpiredAssignments(): int
    {
        $table = config('lunar.database.table_prefix') . 'product_badge_product';
        
        return DB::table($table)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }
}

