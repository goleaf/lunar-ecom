<?php

namespace App\Lunar\Discounts;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Lunar\Facades\Discounts;
use Lunar\Models\Discount;
use Lunar\Models\DiscountPurchasable;
use Lunar\Models\ProductVariant;

/**
 * Helper class for working with Lunar Discounts.
 * 
 * Provides convenience methods for managing discounts, discount purchasables, and applying discounts.
 * See: https://docs.lunarphp.com/1.x/reference/discounts
 */
class DiscountHelper
{
    /**
     * Create a new discount.
     * 
     * @param array $data Discount data (name, handle, type, data, starts_at, ends_at, max_uses, priority, stop)
     * @return Discount
     */
    public static function create(array $data): Discount
    {
        return Discount::create($data);
    }

    /**
     * Create a coupon discount.
     * 
     * @param string $name Discount name
     * @param string $handle Unique handle
     * @param string $couponCode Coupon code (e.g., 'SAVE20')
     * @param array $options Optional: min_prices, starts_at, ends_at, max_uses, priority, stop
     * @return Discount
     */
    public static function createCoupon(string $name, string $handle, string $couponCode, array $options = []): Discount
    {
        return Discount::create([
            'name' => $name,
            'handle' => $handle,
            'type' => 'Lunar\DiscountTypes\Coupon',
            'data' => array_merge([
                'coupon' => $couponCode,
            ], $options['data'] ?? []),
            'starts_at' => $options['starts_at'] ?? now(),
            'ends_at' => $options['ends_at'] ?? null,
            'max_uses' => $options['max_uses'] ?? null,
            'priority' => $options['priority'] ?? 1,
            'stop' => $options['stop'] ?? false,
        ]);
    }

    /**
     * Find a discount by ID.
     * 
     * @param int $id
     * @return Discount|null
     */
    public static function find(int $id): ?Discount
    {
        return Discount::find($id);
    }

    /**
     * Find a discount by handle.
     * 
     * @param string $handle
     * @return Discount|null
     */
    public static function findByHandle(string $handle): ?Discount
    {
        return Discount::where('handle', $handle)->first();
    }

    /**
     * Get active discounts (between starts_at and ends_at).
     * 
     * @return Collection
     */
    public static function getActive(): Collection
    {
        return Discount::active()->get();
    }

    /**
     * Get usable discounts (uses < max_uses or max_uses is null).
     * 
     * @return Collection
     */
    public static function getUsable(): Collection
    {
        return Discount::usable()->get();
    }

    /**
     * Get active and usable discounts.
     * 
     * @return Collection
     */
    public static function getAvailable(): Collection
    {
        return Discount::active()->usable()->get();
    }

    /**
     * Query discounts by associated products.
     * 
     * @param array|Collection $productIds
     * @param string $type 'condition' or 'reward' (default: 'condition')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getByProducts(array|Collection $productIds, string $type = 'condition')
    {
        if ($productIds instanceof Collection) {
            $productIds = $productIds->toArray();
        }
        return Discount::products($productIds, $type);
    }

    /**
     * Reset the discount cache.
     * 
     * Useful after adding a discount code or modifying discounts.
     * 
     * @return void
     */
    public static function resetCache(): void
    {
        Discount::resetDiscounts();
    }

    /**
     * Add a purchasable condition to a discount.
     * 
     * @param Discount $discount
     * @param ProductVariant $purchasable
     * @return DiscountPurchasable
     */
    public static function addCondition(Discount $discount, ProductVariant $purchasable): DiscountPurchasable
    {
        return $discount->purchasables()->create([
            'purchasable_type' => $purchasable->getMorphClass(),
            'purchasable_id' => $purchasable->id,
            'type' => 'condition',
        ]);
    }

    /**
     * Add a purchasable reward to a discount.
     * 
     * @param Discount $discount
     * @param ProductVariant $purchasable
     * @return DiscountPurchasable
     */
    public static function addReward(Discount $discount, ProductVariant $purchasable): DiscountPurchasable
    {
        return $discount->purchasables()->create([
            'purchasable_type' => $purchasable->getMorphClass(),
            'purchasable_id' => $purchasable->id,
            'type' => 'reward',
        ]);
    }

    /**
     * Add multiple purchasable conditions to a discount.
     * 
     * @param Discount $discount
     * @param array|Collection $purchasables
     * @return Collection
     */
    public static function addConditions(Discount $discount, array|Collection $purchasables): Collection
    {
        if (!($purchasables instanceof Collection)) {
            $purchasables = collect($purchasables);
        }

        return $purchasables->map(function ($purchasable) use ($discount) {
            return static::addCondition($discount, $purchasable);
        });
    }

    /**
     * Add multiple purchasable rewards to a discount.
     * 
     * @param Discount $discount
     * @param array|Collection $purchasables
     * @return Collection
     */
    public static function addRewards(Discount $discount, array|Collection $purchasables): Collection
    {
        if (!($purchasables instanceof Collection)) {
            $purchasables = collect($purchasables);
        }

        return $purchasables->map(function ($purchasable) use ($discount) {
            return static::addReward($discount, $purchasable);
        });
    }

    /**
     * Get conditions for a discount.
     * 
     * @param Discount $discount
     * @return Collection
     */
    public static function getConditions(Discount $discount): Collection
    {
        return $discount->purchasables()->where('type', 'condition')->get();
    }

    /**
     * Get rewards for a discount.
     * 
     * @param Discount $discount
     * @return Collection
     */
    public static function getRewards(Discount $discount): Collection
    {
        return $discount->purchasables()->where('type', 'reward')->get();
    }

    /**
     * Register a custom discount type.
     * 
     * @param string $class Class name of the discount type (must extend AbstractDiscountType)
     * @return void
     */
    public static function registerType(string $class): void
    {
        Discounts::addType($class);
    }

    /**
     * Increment the uses count for a discount.
     * 
     * @param Discount $discount
     * @return Discount
     */
    public static function incrementUses(Discount $discount): Discount
    {
        $discount->increment('uses');
        return $discount->fresh();
    }

    /**
     * Check if a discount can be used (uses < max_uses or max_uses is null).
     * 
     * @param Discount $discount
     * @return bool
     */
    public static function canUse(Discount $discount): bool
    {
        if ($discount->max_uses === null) {
            return true;
        }

        return $discount->uses < $discount->max_uses;
    }

    /**
     * Check if a discount is currently active.
     * 
     * @param Discount $discount
     * @return bool
     */
    public static function isActive(Discount $discount): bool
    {
        $now = now();

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return false;
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}


