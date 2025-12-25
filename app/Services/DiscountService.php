<?php

namespace App\Services;

use App\Lunar\Discounts\DiscountHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Lunar\Models\Discount;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\Category;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Customer;

/**
 * Advanced Discount Service for Lunar E-commerce
 * 
 * Provides convenient builder methods for creating various types of discounts:
 * - Percentage discounts with conditions
 * - Fixed amount discounts
 * - BOGO (Buy One Get One) discounts
 * - Category-specific discounts
 * - Product-specific discounts
 * - User group discounts
 * - Time-based discounts
 * 
 * @example
 * // Create 10% off orders over €50
 * $discount = DiscountService::percentageDiscount('10% Off Over €50', '10_percent_over_50')
 *     ->percentage(10)
 *     ->minCartValue(5000) // €50 in cents
 *     ->create();
 */
class DiscountService
{
    protected string $name;
    protected string $handle;
    protected ?string $couponCode = null;
    protected ?int $percentage = null;
    protected ?int $fixedAmount = null;
    protected ?int $minCartValue = null;
    protected ?int $maxDiscountAmount = null;
    protected ?Carbon $startsAt = null;
    protected ?Carbon $endsAt = null;
    protected ?int $maxUses = null;
    protected int $priority = 1;
    protected bool $stop = false;
    protected array $data = [];
    protected Collection $conditions;
    protected Collection $rewards;
    protected Collection $productIds;
    protected Collection $categoryIds;
    protected Collection $collectionIds;
    protected Collection $customerGroupIds;
    protected Collection $customerIds;
    protected array $allowedDays = [];
    protected ?string $allowedTimeStart = null;
    protected ?string $allowedTimeEnd = null;
    protected string $discountTypeClass;

    public function __construct()
    {
        $this->conditions = collect();
        $this->rewards = collect();
        $this->productIds = collect();
        $this->categoryIds = collect();
        $this->collectionIds = collect();
        $this->customerGroupIds = collect();
        $this->customerIds = collect();
        $this->startsAt = now();
    }

    /**
     * Create a percentage discount
     */
    public static function percentageDiscount(string $name, string $handle): self
    {
        $instance = new self();
        $instance->name = $name;
        $instance->handle = $handle;
        $instance->discountTypeClass = \App\Lunar\Discounts\DiscountTypes\PercentageDiscount::class;
        return $instance;
    }

    /**
     * Create a fixed amount discount
     */
    public static function fixedAmountDiscount(string $name, string $handle): self
    {
        $instance = new self();
        $instance->name = $name;
        $instance->handle = $handle;
        $instance->discountTypeClass = \App\Lunar\Discounts\DiscountTypes\FixedAmountDiscount::class;
        return $instance;
    }

    /**
     * Create a BOGO (Buy One Get One) discount
     */
    public static function bogoDiscount(string $name, string $handle): self
    {
        $instance = new self();
        $instance->name = $name;
        $instance->handle = $handle;
        $instance->discountTypeClass = \App\Lunar\Discounts\DiscountTypes\BOGODiscount::class;
        return $instance;
    }

    /**
     * Create a category-specific discount
     */
    public static function categoryDiscount(string $name, string $handle): self
    {
        $instance = new self();
        $instance->name = $name;
        $instance->handle = $handle;
        $instance->discountTypeClass = \App\Lunar\Discounts\DiscountTypes\CategoryDiscount::class;
        return $instance;
    }

    /**
     * Create a product-specific discount
     */
    public static function productDiscount(string $name, string $handle): self
    {
        $instance = new self();
        $instance->name = $name;
        $instance->handle = $handle;
        $instance->discountTypeClass = \App\Lunar\Discounts\DiscountTypes\ProductDiscount::class;
        return $instance;
    }

    /**
     * Set the discount percentage (for percentage discounts)
     */
    public function percentage(int $percentage): self
    {
        $this->percentage = $percentage;
        $this->data['percentage'] = $percentage;
        return $this;
    }

    /**
     * Set the fixed discount amount in cents
     */
    public function fixedAmount(int $amount): self
    {
        $this->fixedAmount = $amount;
        $this->data['fixed_amount'] = $amount;
        return $this;
    }

    /**
     * Set minimum cart value in cents
     */
    public function minCartValue(int $amount): self
    {
        $this->minCartValue = $amount;
        $this->data['min_cart_value'] = $amount;
        return $this;
    }

    /**
     * Set maximum discount amount in cents (caps the discount)
     */
    public function maxDiscountAmount(int $amount): self
    {
        $this->maxDiscountAmount = $amount;
        $this->data['max_discount_amount'] = $amount;
        return $this;
    }

    /**
     * Set coupon code
     */
    public function couponCode(string $code): self
    {
        $this->couponCode = strtoupper(trim($code));
        return $this;
    }

    /**
     * Set start date/time
     */
    public function startsAt(Carbon|string $date): self
    {
        $this->startsAt = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $this;
    }

    /**
     * Set end date/time
     */
    public function endsAt(Carbon|string|null $date): self
    {
        $this->endsAt = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : null;
        return $this;
    }

    /**
     * Set maximum number of uses
     */
    public function maxUses(?int $maxUses): self
    {
        $this->maxUses = $maxUses;
        return $this;
    }

    /**
     * Set priority (higher = more priority)
     */
    public function priority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Set whether this discount stops other discounts from applying
     */
    public function stop(bool $stop = true): self
    {
        $this->stop = $stop;
        return $this;
    }

    /**
     * Add products that must be in cart (conditions)
     */
    public function requireProducts(array|Collection $products): self
    {
        $products = $products instanceof Collection ? $products : collect($products);
        
        $products->each(function ($product) {
            if ($product instanceof Product) {
                $this->productIds->push($product->id);
            } elseif ($product instanceof ProductVariant) {
                $this->conditions->push($product);
            } elseif (is_int($product)) {
                $this->productIds->push($product);
            }
        });
        
        return $this;
    }

    /**
     * Add products that get the discount (rewards)
     */
    public function applyToProducts(array|Collection $products): self
    {
        $products = $products instanceof Collection ? $products : collect($products);
        
        $products->each(function ($product) {
            if ($product instanceof ProductVariant) {
                $this->rewards->push($product);
            } elseif ($product instanceof Product) {
                // Get all variants for the product
                $product->variants->each(fn($variant) => $this->rewards->push($variant));
            }
        });
        
        return $this;
    }

    /**
     * Add categories that must be in cart (conditions)
     */
    public function requireCategories(array|Collection $categories): self
    {
        $categories = $categories instanceof Collection ? $categories : collect($categories);
        
        $categories->each(function ($category) {
            if ($category instanceof Category) {
                $this->categoryIds->push($category->id);
            } elseif (is_int($category)) {
                $this->categoryIds->push($category);
            }
        });
        
        $this->data['required_categories'] = $this->categoryIds->toArray();
        return $this;
    }

    /**
     * Add categories that get the discount (rewards)
     */
    public function applyToCategories(array|Collection $categories): self
    {
        $categories = $categories instanceof Collection ? $categories : collect($categories);
        
        $categories->each(function ($category) {
            if ($category instanceof Category) {
                $this->categoryIds->push($category->id);
            } elseif (is_int($category)) {
                $this->categoryIds->push($category);
            }
        });
        
        $this->data['target_categories'] = $this->categoryIds->toArray();
        return $this;
    }

    /**
     * Add collections that must be in cart (conditions)
     */
    public function requireCollections(array|Collection $collections): self
    {
        $collections = $collections instanceof Collection ? $collections : collect($collections);
        
        $collections->each(function ($collection) {
            if ($collection instanceof LunarCollection) {
                $this->collectionIds->push($collection->id);
            } elseif (is_int($collection)) {
                $this->collectionIds->push($collection);
            }
        });
        
        $this->data['required_collections'] = $this->collectionIds->toArray();
        return $this;
    }

    /**
     * Limit discount to specific customer groups
     */
    public function forCustomerGroups(array|Collection $customerGroups): self
    {
        $customerGroups = $customerGroups instanceof Collection ? $customerGroups : collect($customerGroups);
        
        $customerGroups->each(function ($group) {
            if ($group instanceof CustomerGroup) {
                $this->customerGroupIds->push($group->id);
            } elseif (is_int($group)) {
                $this->customerGroupIds->push($group);
            }
        });
        
        $this->data['customer_groups'] = $this->customerGroupIds->toArray();
        return $this;
    }

    /**
     * Limit discount to specific customers
     */
    public function forCustomers(array|Collection $customers): self
    {
        $customers = $customers instanceof Collection ? $customers : collect($customers);
        
        $customers->each(function ($customer) {
            if ($customer instanceof Customer) {
                $this->customerIds->push($customer->id);
            } elseif (is_int($customer)) {
                $this->customerIds->push($customer);
            }
        });
        
        return $this;
    }

    /**
     * Set allowed days of week (0 = Sunday, 6 = Saturday)
     */
    public function allowedDays(array $days): self
    {
        $this->allowedDays = $days;
        $this->data['allowed_days'] = $days;
        return $this;
    }

    /**
     * Set allowed time window (e.g., '09:00', '17:00')
     */
    public function allowedTimeWindow(string $start, string $end): self
    {
        $this->allowedTimeStart = $start;
        $this->allowedTimeEnd = $end;
        $this->data['time_start'] = $start;
        $this->data['time_end'] = $end;
        return $this;
    }

    /**
     * Set additional data
     */
    public function withData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Create the discount
     */
    public function create(): Discount
    {
        // Validate required fields
        if (empty($this->name) || empty($this->handle)) {
            throw new \InvalidArgumentException('Name and handle are required');
        }

        // Build discount data
        $discountData = [
            'name' => $this->name,
            'handle' => $this->handle,
            'type' => $this->discountTypeClass,
            'coupon' => $this->couponCode,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'max_uses' => $this->maxUses,
            'priority' => $this->priority,
            'stop' => $this->stop,
            'data' => $this->data,
        ];

        // Create the discount
        $discount = Discount::create($discountData);

        // Attach conditions
        $this->conditions->each(function ($purchasable) use ($discount) {
            DiscountHelper::addCondition($discount, $purchasable);
        });

        // Attach rewards
        $this->rewards->each(function ($purchasable) use ($discount) {
            DiscountHelper::addReward($discount, $purchasable);
        });

        // Attach customer groups (if using Lunar's customer group discount relationship)
        if ($this->customerGroupIds->isNotEmpty()) {
            $discount->customerGroups()->sync($this->customerGroupIds->toArray());
        }

        // Attach customers (if using Lunar's customer discount relationship)
        if ($this->customerIds->isNotEmpty()) {
            $discount->customers()->sync($this->customerIds->toArray());
        }

        return $discount;
    }
}

