<?php

namespace App\Services;

use App\Models\Bundle;
use App\Models\BundleAnalytic;
use App\Models\BundleItem;
use App\Services\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

/**
 * Service for managing product bundles and kits.
 */
class BundleService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Calculate bundle price.
     *
     * @param  Bundle  $bundle
     * @param  array|null  $selectedItems  For dynamic bundles: [['product_id' => X, 'variant_id' => Y, 'quantity' => Z], ...]
     * @return array
     */
    public function calculateBundlePrice(Bundle $bundle, ?array $selectedItems = null): array
    {
        $items = $this->getBundleItems($bundle, $selectedItems);
        
        $originalPrice = 0;
        $itemPrices = [];

        foreach ($items as $item) {
            $itemPrice = $item->getPrice() * $item->quantity;
            $originalPrice += $itemPrice;
            
            $itemPrices[] = [
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product->translateAttribute('name'),
                'quantity' => $item->quantity,
                'unit_price' => $item->getPrice(),
                'total_price' => $itemPrice,
            ];
        }

        // Calculate discount
        $discountAmount = 0;
        if ($bundle->discount_type === 'percentage') {
            $discountAmount = ($originalPrice * $bundle->discount_value) / 100;
        } else {
            $discountAmount = $bundle->discount_value * 100; // Convert to cents
        }

        $bundlePrice = max(0, $originalPrice - $discountAmount);
        $savingsAmount = $originalPrice - $bundlePrice;
        $savingsPercentage = $originalPrice > 0 ? round(($savingsAmount / $originalPrice) * 100, 2) : 0;

        return [
            'original_price' => $originalPrice,
            'bundle_price' => $bundlePrice,
            'discount_amount' => $discountAmount,
            'savings_amount' => $savingsAmount,
            'savings_percentage' => $savingsPercentage,
            'items' => $itemPrices,
            'discount_type' => $bundle->discount_type,
            'discount_value' => $bundle->discount_value,
        ];
    }

    /**
     * Validate bundle availability.
     *
     * @param  Bundle  $bundle
     * @param  array|null  $selectedItems
     * @param  int  $requestedQuantity
     * @return array
     */
    public function validateBundleAvailability(Bundle $bundle, ?array $selectedItems = null, int $requestedQuantity = 1): array
    {
        $items = $this->getBundleItems($bundle, $selectedItems);
        
        $isAvailable = true;
        $unavailableItems = [];
        $availabilityDetails = [];

        foreach ($items as $item) {
            $variant = $item->productVariant;
            if (!$variant) {
                $variant = $item->product->variants->first();
            }

            if (!$variant) {
                $isAvailable = false;
                $unavailableItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->translateAttribute('name'),
                    'reason' => 'No variant available',
                ];
                continue;
            }

            $requiredQuantity = $item->quantity * $requestedQuantity;
            $availability = $this->inventoryService->checkAvailability($variant, $requiredQuantity);

            $availabilityDetails[] = [
                'product_id' => $item->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $item->product->translateAttribute('name'),
                'required_quantity' => $requiredQuantity,
                'available' => $availability['available'],
                'total_available' => $availability['total_available'],
            ];

            if (!$availability['available']) {
                $isAvailable = false;
                $unavailableItems[] = [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $item->product->translateAttribute('name'),
                    'required_quantity' => $requiredQuantity,
                    'available_quantity' => $availability['total_available'],
                    'reason' => 'Insufficient stock',
                ];
            }
        }

        return [
            'is_available' => $isAvailable,
            'unavailable_items' => $unavailableItems,
            'availability_details' => $availabilityDetails,
        ];
    }

    /**
     * Add bundle to cart.
     *
     * @param  Bundle  $bundle
     * @param  Cart  $cart
     * @param  int  $quantity
     * @param  array|null  $selectedItems
     * @return array
     * @throws \Exception
     */
    public function addBundleToCart(Bundle $bundle, Cart $cart, int $quantity = 1, ?array $selectedItems = null): array
    {
        // Validate availability
        $availability = $this->validateBundleAvailability($bundle, $selectedItems, $quantity);
        if (!$availability['is_available']) {
            throw new \Exception('Bundle items are not available: ' . json_encode($availability['unavailable_items']));
        }

        // Calculate price
        $pricing = $this->calculateBundlePrice($bundle, $selectedItems);

        // For fixed bundles, add as a single cart line
        // For dynamic bundles, we might add individual items or a bundle line
        if ($bundle->isFixed()) {
            // Add bundle as single line with metadata
            $cartLine = $cart->lines()->create([
                'purchasable_type' => ProductVariant::class,
                'purchasable_id' => $bundle->product->variants->first()->id ?? null,
                'quantity' => $quantity,
                'meta' => [
                    'is_bundle' => true,
                    'bundle_id' => $bundle->id,
                    'bundle_items' => $this->getBundleItems($bundle, $selectedItems)->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'quantity' => $item->quantity,
                        ];
                    })->toArray(),
                    'original_price' => $pricing['original_price'],
                    'bundle_price' => $pricing['bundle_price'],
                    'savings_amount' => $pricing['savings_amount'],
                ],
            ]);

            // Track analytics
            $this->trackBundleEvent($bundle, 'add_to_cart', $selectedItems, $pricing);
            $bundle->incrementAddToCart();

            return [
                'cart_line' => $cartLine,
                'pricing' => $pricing,
            ];
        } else {
            // Dynamic bundle: add individual items with bundle discount applied
            $items = $this->getBundleItems($bundle, $selectedItems);
            $cartLines = collect();

            foreach ($items as $item) {
                $variant = $item->productVariant;
                if (!$variant) {
                    $variant = $item->product->variants->first();
                }

                if (!$variant) {
                    continue;
                }

                // Calculate item price with bundle discount
                $itemPrice = $this->calculateItemPriceInBundle($item, $bundle, $pricing);

                $cartLine = $cart->lines()->create([
                    'purchasable_type' => ProductVariant::class,
                    'purchasable_id' => $variant->id,
                    'quantity' => $item->quantity * $quantity,
                    'meta' => [
                        'is_bundle_item' => true,
                        'bundle_id' => $bundle->id,
                        'bundle_item_id' => $item->id,
                        'original_price' => $item->getPrice(),
                        'bundle_price' => $itemPrice,
                    ],
                ]);

                $cartLines->push($cartLine);
            }

            // Track analytics
            $this->trackBundleEvent($bundle, 'add_to_cart', $selectedItems, $pricing);
            $bundle->incrementAddToCart();

            return [
                'cart_lines' => $cartLines,
                'pricing' => $pricing,
            ];
        }
    }

    /**
     * Get bundle items (for fixed or dynamic bundles).
     *
     * @param  Bundle  $bundle
     * @param  array|null  $selectedItems
     * @return Collection
     */
    protected function getBundleItems(Bundle $bundle, ?array $selectedItems = null): Collection
    {
        if ($bundle->isFixed()) {
            return $bundle->items;
        }

        // Dynamic bundle: build items from selected items
        if (!$selectedItems) {
            return collect();
        }

        $items = collect();
        foreach ($selectedItems as $selected) {
            $product = Product::find($selected['product_id']);
            if (!$product) {
                continue;
            }

            $variant = isset($selected['product_variant_id']) 
                ? ProductVariant::find($selected['product_variant_id'])
                : $product->variants->first();

            if (!$variant) {
                continue;
            }

            // Create a temporary bundle item for calculation
            $bundleItem = new BundleItem([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $selected['quantity'] ?? 1,
            ]);
            $bundleItem->setRelation('product', $product);
            $bundleItem->setRelation('productVariant', $variant);

            $items->push($bundleItem);
        }

        return $items;
    }

    /**
     * Calculate item price in bundle (with discount applied).
     *
     * @param  BundleItem  $item
     * @param  Bundle  $bundle
     * @param  array  $pricing
     * @return int  Price in cents
     */
    protected function calculateItemPriceInBundle(BundleItem $item, Bundle $bundle, array $pricing): int
    {
        $originalItemPrice = $item->getPrice() * $item->quantity;
        
        // Apply proportional discount
        if ($pricing['original_price'] > 0) {
            $proportionalDiscount = ($originalItemPrice / $pricing['original_price']) * $pricing['discount_amount'];
            return (int) ($originalItemPrice - $proportionalDiscount);
        }

        return $originalItemPrice;
    }

    /**
     * Get available products for "Build Your Own Bundle".
     *
     * @param  Bundle  $bundle
     * @return Collection
     */
    public function getAvailableProductsForBundle(Bundle $bundle): Collection
    {
        if (!$bundle->category_id) {
            return collect();
        }

        return Product::whereHas('categories', function ($q) use ($bundle) {
            $q->where('categories.id', $bundle->category_id);
        })
        ->where('status', 'published')
        ->where('id', '!=', $bundle->product_id)
        ->with(['variants.prices', 'variants.stock'])
        ->get();
    }

    /**
     * Validate dynamic bundle selection.
     *
     * @param  Bundle  $bundle
     * @param  array  $selectedItems
     * @return array
     */
    public function validateDynamicBundleSelection(Bundle $bundle, array $selectedItems): array
    {
        $errors = [];
        $selectedCount = count($selectedItems);

        // Check min/max items
        if ($bundle->min_items && $selectedCount < $bundle->min_items) {
            $errors[] = "Minimum {$bundle->min_items} item(s) required";
        }

        if ($bundle->max_items && $selectedCount > $bundle->max_items) {
            $errors[] = "Maximum {$bundle->max_items} item(s) allowed";
        }

        // Validate group constraints
        $groupSelections = collect($selectedItems)->groupBy('group_name');
        foreach ($bundle->items as $item) {
            if ($item->group_name && ($item->group_min_selection || $item->group_max_selection)) {
                $groupSelected = $groupSelections->get($item->group_name, collect())->count();
                
                if ($item->group_min_selection && $groupSelected < $item->group_min_selection) {
                    $errors[] = "Group '{$item->group_name}' requires at least {$item->group_min_selection} selection(s)";
                }

                if ($item->group_max_selection && $groupSelected > $item->group_max_selection) {
                    $errors[] = "Group '{$item->group_name}' allows maximum {$item->group_max_selection} selection(s)";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Track bundle analytics event.
     *
     * @param  Bundle  $bundle
     * @param  string  $eventType
     * @param  array|null  $selectedItems
     * @param  array|null  $pricing
     * @return void
     */
    public function trackBundleEvent(Bundle $bundle, string $eventType, ?array $selectedItems = null, ?array $pricing = null): void
    {
        BundleAnalytic::create([
            'bundle_id' => $bundle->id,
            'event_type' => $eventType,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'selected_items' => $selectedItems,
            'bundle_price' => $pricing['bundle_price'] ?? null,
            'original_price' => $pricing['original_price'] ?? null,
            'savings_amount' => $pricing['savings_amount'] ?? null,
            'savings_percentage' => $pricing['savings_percentage'] ?? null,
            'event_at' => now(),
        ]);
    }

    /**
     * Handle bundle return.
     *
     * @param  Bundle  $bundle
     * @param  \Lunar\Models\Order  $order
     * @param  array|null  $returnedItems  If null, return entire bundle
     * @return array
     */
    public function handleBundleReturn(Bundle $bundle, \Lunar\Models\Order $order, ?array $returnedItems = null): array
    {
        if (!$bundle->allow_individual_returns && $returnedItems !== null) {
            throw new \Exception('Individual item returns not allowed for this bundle');
        }

        // Track return analytics
        $this->trackBundleEvent($bundle, 'return', $returnedItems);

        return [
            'bundle_id' => $bundle->id,
            'returned_items' => $returnedItems,
            'full_bundle_return' => $returnedItems === null,
        ];
    }

    /**
     * Get bundle analytics.
     *
     * @param  Bundle  $bundle
     * @param  int|null  $days
     * @return array
     */
    public function getBundleAnalytics(Bundle $bundle, ?int $days = null): array
    {
        $query = BundleAnalytic::where('bundle_id', $bundle->id);

        if ($days) {
            $query->where('event_at', '>=', now()->subDays($days));
        }

        $analytics = $query->get();

        return [
            'total_views' => $bundle->view_count,
            'total_add_to_cart' => $bundle->add_to_cart_count,
            'total_purchases' => $bundle->purchase_count,
            'conversion_rate' => $bundle->view_count > 0 
                ? round(($bundle->purchase_count / $bundle->view_count) * 100, 2) 
                : 0,
            'add_to_cart_rate' => $bundle->view_count > 0
                ? round(($bundle->add_to_cart_count / $bundle->view_count) * 100, 2)
                : 0,
            'average_savings_amount' => $analytics->whereNotNull('savings_amount')->avg('savings_amount'),
            'average_savings_percentage' => $analytics->whereNotNull('savings_percentage')->avg('savings_percentage'),
            'total_savings' => $analytics->whereNotNull('savings_amount')->sum('savings_amount'),
        ];
    }
}
