<?php

namespace App\Services;

use App\Models\Bundle;
use App\Models\BundleAnalytic;
use App\Models\BundleItem;
use App\Models\BundlePrice;
use App\Services\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lunar\Facades\Currency;
use Lunar\Facades\StorefrontSession;
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
     * @param  int  $bundleQuantity
     * @return array
     */
    public function calculateBundlePrice(Bundle $bundle, ?array $selectedItems = null, int $bundleQuantity = 1): array
    {
        $currency = Currency::getDefault();
        $customerGroupId = StorefrontSession::getCustomerGroup()?->id;

        $items = $this->getBundleItems($bundle, $selectedItems);
        
        $originalPrice = 0;
        $itemPrices = [];

        foreach ($items as $item) {
            $itemPrice = $item->getPrice($currency, $customerGroupId);
            $lineTotal = $itemPrice * $item->quantity;
            $originalPrice += $lineTotal;
            
            $itemPrices[] = [
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product?->translateAttribute('name'),
                'quantity' => $item->quantity,
                'unit_price' => $itemPrice,
                'total_price' => $lineTotal,
            ];
        }

        [$bundlePrice, $discountAmount] = $this->calculateBundleTotals($bundle, $originalPrice, $bundleQuantity);

        $bundlePrice = (int) round($bundlePrice);
        $discountAmount = (int) round($discountAmount);
        $originalPrice = (int) round($originalPrice * $bundleQuantity);

        $savingsAmount = max(0, $originalPrice - $bundlePrice);
        $savingsPercentage = $originalPrice > 0 ? round(($savingsAmount / $originalPrice) * 100, 2) : 0;

        return [
            'original_price' => $originalPrice,
            'bundle_price' => $bundlePrice,
            'discount_amount' => $discountAmount,
            'savings_amount' => $savingsAmount,
            'savings_percentage' => $savingsPercentage,
            'items' => $itemPrices,
            'discount_type' => $bundle->pricing_type,
            'discount_value' => $bundle->discount_amount,
        ];
    }

    /**
     * Calculate bundle totals based on pricing type.
     */
    protected function calculateBundleTotals(Bundle $bundle, int $originalPrice, int $bundleQuantity): array
    {
        $pricePerBundle = $originalPrice;
        $discountPerBundle = 0;

        switch ($bundle->pricing_type) {
            case 'fixed':
                if ($bundle->bundle_price) {
                    $pricePerBundle = $bundle->bundle_price;
                    $discountPerBundle = max(0, $originalPrice - $bundle->bundle_price);
                } else {
                    $discountPerBundle = $bundle->discount_amount ?? 0;
                    $pricePerBundle = max(0, $originalPrice - $discountPerBundle);
                }
                break;
            case 'percentage':
                $discountPerBundle = (int) round($originalPrice * (($bundle->discount_amount ?? 0) / 100));
                $pricePerBundle = max(0, $originalPrice - $discountPerBundle);
                break;
            case 'dynamic':
            default:
                $pricePerBundle = $originalPrice;
                $discountPerBundle = 0;
        }

        return [$pricePerBundle * $bundleQuantity, $discountPerBundle * $bundleQuantity];
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

        if ($bundle->inventory_type === 'independent' && $bundle->stock < $requestedQuantity) {
            $isAvailable = false;
            $unavailableItems[] = [
                'bundle_id' => $bundle->id,
                'reason' => 'Insufficient bundle stock',
                'required_quantity' => $requestedQuantity,
                'available_quantity' => $bundle->stock,
            ];
        }

        foreach ($items as $item) {
            $variant = $item->getVariant() ?? $item->productVariant ?? $item->product->variants->first();

            if (!$variant) {
                $isAvailable = false;
                $unavailableItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->translateAttribute('name'),
                    'reason' => 'No variant available',
                ];
                continue;
            }

            $requiredQuantity = $item->quantity * $requestedQuantity;
            $availability = $this->inventoryService->checkAvailability($variant, $requiredQuantity);

            $availabilityDetails[] = [
                'product_id' => $item->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $item->product?->translateAttribute('name'),
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
        $pricing = $this->calculateBundlePrice($bundle, $selectedItems, $quantity);

        // For fixed bundles, add as a single cart line
        // For dynamic bundles, we might add individual items or a bundle line
        if ($bundle->isFixed()) {
            $bundleVariant = $bundle->product->variants->first();
            if (!$bundleVariant) {
                throw new \Exception('Bundle product does not have a purchasable variant.');
            }

            // Add bundle as single line with metadata
            $cartLine = $cart->lines()->create([
                'purchasable_type' => ProductVariant::class,
                'purchasable_id' => $bundleVariant->id,
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

            return [
                'cart_line' => $cartLine,
                'pricing' => $pricing,
            ];
        } else {
            // Dynamic bundle: add individual items with bundle discount applied
            $items = $this->getBundleItems($bundle, $selectedItems);
            $cartLines = collect();

            foreach ($items as $item) {
                $variant = $item->getVariant() ?? $item->productVariant ?? $item->product->variants->first();

                if (!$variant) {
                    continue;
                }

                // Calculate item price with bundle discount
                $itemPrice = $this->calculateItemPriceInBundle($item, $bundle, $pricing, $quantity);

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

            return [
                'cart_lines' => $cartLines,
                'pricing' => $pricing,
            ];
        }
    }

    /**
     * Create a bundle with items and price tiers.
     */
    public function createBundle(array $data): Bundle
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            $prices = $data['prices'] ?? [];

            unset($data['items'], $data['prices']);

            $data['stock'] = $data['stock'] ?? 0;
            $data['min_quantity'] = $data['min_quantity'] ?? 1;
            $data['display_order'] = $data['display_order'] ?? 0;

            $bundle = Bundle::create($data);

            if ($items) {
                $this->syncBundleItems($bundle, $items);
            }

            if ($prices) {
                $this->syncBundlePrices($bundle, $prices);
            }

            return $bundle->load(['items.product', 'items.productVariant', 'prices']);
        });
    }

    /**
     * Update a bundle and optionally sync items/prices.
     */
    public function updateBundle(Bundle $bundle, array $data): Bundle
    {
        return DB::transaction(function () use ($bundle, $data) {
            $items = $data['items'] ?? null;
            $prices = $data['prices'] ?? null;

            unset($data['items'], $data['prices']);

            foreach (['stock' => 0, 'min_quantity' => 1, 'display_order' => 0] as $field => $default) {
                if (array_key_exists($field, $data)) {
                    $data[$field] = $data[$field] ?? $default;
                }
            }

            $bundle->fill($data);
            $bundle->save();

            if (is_array($items)) {
                $this->syncBundleItems($bundle, $items);
            }

            if (is_array($prices)) {
                $this->syncBundlePrices($bundle, $prices);
            }

            return $bundle->load(['items.product', 'items.productVariant', 'prices']);
        });
    }

    /**
     * Add a new item to an existing bundle.
     */
    public function addBundleItem(Bundle $bundle, array $data): BundleItem
    {
        $displayOrder = ($bundle->items()->max('display_order') ?? 0) + 1;

        $item = $bundle->items()->create([
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'min_quantity' => $data['min_quantity'] ?? 1,
            'max_quantity' => $data['max_quantity'] ?? null,
            'is_required' => $data['is_required'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'price_override' => $data['price_override'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? null,
            'display_order' => $data['display_order'] ?? $displayOrder,
            'notes' => $data['notes'] ?? null,
        ]);

        return $item->load(['product', 'productVariant']);
    }

    /**
     * Add a price tier to a bundle.
     */
    public function addBundlePrice(Bundle $bundle, array $data): BundlePrice
    {
        $price = $bundle->prices()->create([
            'currency_id' => $data['currency_id'],
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'min_quantity' => $data['min_quantity'] ?? 1,
            'max_quantity' => $data['max_quantity'] ?? null,
        ]);

        return $price->fresh();
    }

    /**
     * Get active bundles ready for display.
     */
    public function getAvailableBundles(?int $limit = null): Collection
    {
        $query = Bundle::with(['product.media', 'items'])->active()->orderBy('display_order');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
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
        $bundle->loadMissing(['items.product.variants', 'items.productVariant']);

        $hasDynamicPayload = is_array($selectedItems)
            && array_is_list($selectedItems)
            && isset($selectedItems[0]['product_id']);

        if (!$bundle->isDynamic() && !$hasDynamicPayload) {
            $items = $bundle->items;

            if (!$selectedItems) {
                return $items;
            }

            return $items->map(function (BundleItem $item) use ($selectedItems) {
                $providedQuantity = $selectedItems[$item->id] ?? $selectedItems[(string) $item->id] ?? $item->quantity;
                $quantity = is_numeric($providedQuantity) ? (int) $providedQuantity : $item->quantity;

                $minimum = $item->is_required ? ($item->min_quantity ?? 1) : 0;
                $maximum = $item->max_quantity;

                $quantity = max($minimum, $quantity);
                if ($maximum !== null) {
                    $quantity = min($quantity, $maximum);
                }

                if (!$item->is_required && $quantity <= 0) {
                    return null;
                }

                $clone = clone $item;
                $clone->quantity = $quantity;

                return $clone;
            })->filter()->values();
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
     * Replace bundle items with the provided collection.
     */
    protected function syncBundleItems(Bundle $bundle, array $items): void
    {
        $bundle->items()->delete();
        $displayOrder = 0;

        foreach ($items as $itemData) {
            $bundle->items()->create([
                'product_id' => $itemData['product_id'],
                'product_variant_id' => $itemData['product_variant_id'] ?? null,
                'quantity' => $itemData['quantity'] ?? 1,
                'min_quantity' => $itemData['min_quantity'] ?? 1,
                'max_quantity' => $itemData['max_quantity'] ?? null,
                'is_required' => $itemData['is_required'] ?? true,
                'is_default' => $itemData['is_default'] ?? false,
                'price_override' => $itemData['price_override'] ?? null,
                'discount_amount' => $itemData['discount_amount'] ?? null,
                'display_order' => $itemData['display_order'] ?? $displayOrder++,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }
    }

    /**
     * Replace bundle price tiers.
     */
    protected function syncBundlePrices(Bundle $bundle, array $prices): void
    {
        $bundle->prices()->delete();

        foreach ($prices as $priceData) {
            $bundle->prices()->create([
                'currency_id' => $priceData['currency_id'],
                'customer_group_id' => $priceData['customer_group_id'] ?? null,
                'price' => $priceData['price'],
                'compare_at_price' => $priceData['compare_at_price'] ?? null,
                'min_quantity' => $priceData['min_quantity'] ?? 1,
                'max_quantity' => $priceData['max_quantity'] ?? null,
            ]);
        }
    }

    /**
     * Calculate item price in bundle (with discount applied).
     *
     * @param  BundleItem  $item
     * @param  Bundle  $bundle
     * @param  array  $pricing
     * @param  int  $bundleQuantity
     * @return int  Price in cents
     */
    protected function calculateItemPriceInBundle(BundleItem $item, Bundle $bundle, array $pricing, int $bundleQuantity = 1): int
    {
        $currency = Currency::getDefault();
        $customerGroupId = StorefrontSession::getCustomerGroup()?->id;

        $originalItemPrice = $item->getPrice($currency, $customerGroupId) * $item->quantity * $bundleQuantity;
        
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
        ->published()
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
        $allowIndividualReturns = $bundle->allow_individual_returns ?? true;

        if (!$allowIndividualReturns && $returnedItems !== null) {
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

        $totalViews = $analytics->where('event_type', 'view')->count();
        $totalAddToCart = $analytics->where('event_type', 'add_to_cart')->count();
        $totalPurchases = $analytics->where('event_type', 'purchase')->count();

        return [
            'total_views' => $totalViews,
            'total_add_to_cart' => $totalAddToCart,
            'total_purchases' => $totalPurchases,
            'conversion_rate' => $totalViews > 0 
                ? round(($totalPurchases / $totalViews) * 100, 2) 
                : 0,
            'add_to_cart_rate' => $totalViews > 0
                ? round(($totalAddToCart / $totalViews) * 100, 2)
                : 0,
            'average_savings_amount' => $analytics->whereNotNull('savings_amount')->avg('savings_amount'),
            'average_savings_percentage' => $analytics->whereNotNull('savings_percentage')->avg('savings_percentage'),
            'total_savings' => $analytics->whereNotNull('savings_amount')->sum('savings_amount'),
        ];
    }
}
