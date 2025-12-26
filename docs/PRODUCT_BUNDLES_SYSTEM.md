# Product Bundles & Kits System

## Overview

A comprehensive product bundling system that allows customers to purchase multiple products together at discounted prices. Supports dynamic bundle creation, customization, and flexible inventory management.

## Features

### Core Features

1. **Bundle Types**
   - **Fixed Bundle**: Pre-defined set of products with fixed pricing
   - **Dynamic Bundle**: Customers can customize bundle contents
   - **Percentage Discount**: Discount based on percentage of individual prices
   - **Fixed Discount**: Fixed amount discount
   - **Fixed Price**: Entire bundle at a fixed price

2. **Pricing Models**
   - **Fixed**: Bundle has a fixed price
   - **Percentage**: Discount percentage applied to individual item total
   - **Dynamic**: Price calculated based on selected items

3. **Inventory Management**
   - **Component-based**: Stock based on individual product availability
   - **Independent**: Bundle has its own stock level
   - **Unlimited**: No stock tracking

4. **Bundle Customization**
   - Required items (must be included)
   - Optional items (can be added/removed)
   - Quantity limits per item
   - Default item selection

5. **Price Tiers**
   - Quantity-based pricing
   - Customer group pricing
   - Currency-specific pricing

## Models

### Bundle
- **Location**: `app/Models/Bundle.php`
- **Table**: `lunar_bundles`
- **Key Fields**:
  - `product_id`: Associated product (optional)
  - `name`, `description`, `slug`, `sku`
  - `pricing_type`: fixed, percentage, dynamic
  - `discount_amount`: Discount in cents or percentage
  - `bundle_price`: Fixed bundle price
  - `inventory_type`: component, independent, unlimited
  - `stock`: Stock level (for independent inventory)
  - `allow_customization`: Allow customers to modify items
  - `show_individual_prices`: Show individual item prices
  - `show_savings`: Show savings amount

### BundleItem
- **Location**: `app/Models/BundleItem.php`
- **Table**: `lunar_bundle_items`
- **Key Fields**:
  - `bundle_id`, `product_id`, `product_variant_id`
  - `quantity`: Default quantity
  - `min_quantity`, `max_quantity`: Quantity limits
  - `is_required`: Required or optional item
  - `is_default`: Default selected for optional items
  - `price_override`: Override product price
  - `discount_amount`: Item-specific discount

### BundlePrice
- **Location**: `app/Models/BundlePrice.php`
- **Table**: `lunar_bundle_prices`
- **Key Fields**:
  - `bundle_id`, `currency_id`, `customer_group_id`
  - `price`: Bundle price in cents
  - `compare_at_price`: Original total price
  - `min_quantity`, `max_quantity`: Quantity tier

## Services

### BundleService
- **Location**: `app/Services/BundleService.php`
- **Methods**:
  - `createBundle()`: Create a new bundle
  - `updateBundle()`: Update existing bundle
  - `addBundleItem()`: Add item to bundle
  - `addBundlePrice()`: Add price tier
  - `addToCart()`: Add bundle to cart
  - `validateBundle()`: Validate bundle availability
  - `getAvailableBundles()`: Get active bundles

## Controllers

### Frontend\BundleController
- `index()`: Display all bundles
- `show()`: Display single bundle
- `addToCart()`: Add bundle to cart
- `calculatePrice()`: Calculate bundle price (AJAX)

### Admin\BundleController
- `index()`: List all bundles
- `create()`: Show creation form
- `store()`: Create new bundle
- `edit()`: Show edit form
- `update()`: Update bundle
- `destroy()`: Delete bundle

## Routes

### Frontend
```php
Route::prefix('bundles')->name('frontend.bundles.')->group(function () {
    Route::get('/', [BundleController::class, 'index'])->name('index');
    Route::get('/{bundle:slug}', [BundleController::class, 'show'])->name('show');
    Route::post('/{bundle}/add-to-cart', [BundleController::class, 'addToCart'])->name('add-to-cart');
    Route::get('/{bundle}/calculate-price', [BundleController::class, 'calculatePrice'])->name('calculate-price');
});
```

### Admin
```php
Route::prefix('admin/bundles')->name('admin.bundles.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [BundleController::class, 'index'])->name('index');
    Route::get('/create', [BundleController::class, 'create'])->name('create');
    Route::post('/', [BundleController::class, 'store'])->name('store');
    Route::get('/{bundle}/edit', [BundleController::class, 'edit'])->name('edit');
    Route::put('/{bundle}', [BundleController::class, 'update'])->name('update');
    Route::delete('/{bundle}', [BundleController::class, 'destroy'])->name('destroy');
});
```

## Usage Examples

### Create a Bundle
```php
use App\Services\BundleService;

$bundleService = app(BundleService::class);

$bundle = $bundleService->createBundle([
    'product_id' => $product->id,
    'name' => 'Starter Kit',
    'description' => 'Everything you need to get started',
    'pricing_type' => 'percentage',
    'discount_amount' => 15, // 15% discount
    'inventory_type' => 'component',
    'items' => [
        [
            'product_id' => $product1->id,
            'product_variant_id' => $variant1->id,
            'quantity' => 1,
            'is_required' => true,
        ],
        [
            'product_id' => $product2->id,
            'quantity' => 2,
            'is_required' => true,
        ],
        [
            'product_id' => $product3->id,
            'quantity' => 1,
            'is_required' => false,
            'is_default' => true,
        ],
    ],
]);
```

### Add Bundle to Cart
```php
$cart = $bundleService->addToCart(
    $bundle,
    $quantity = 1,
    $selectedItems = [
        $bundleItem1->id => 2, // Custom quantity for optional item
        $bundleItem2->id => 1,
    ]
);
```

### Calculate Bundle Price
```php
$currency = \Lunar\Facades\Currency::getDefault();
$customerGroupId = \Lunar\Facades\StorefrontSession::getCustomerGroup()?->id;

$individualTotal = $bundle->calculateIndividualTotal($currency, $customerGroupId);
$bundlePrice = $bundle->calculatePrice($currency, $customerGroupId, $quantity);
$savings = $bundle->calculateSavings($currency, $customerGroupId);
```

## Bundle Pricing Logic

### Fixed Price
- Bundle has a fixed price set in `bundle_price`
- Can have quantity-based price tiers via `BundlePrice` model
- Price is independent of individual item prices

### Percentage Discount
- Individual item total is calculated
- Discount percentage (`discount_amount`) is applied
- Final price = Individual Total - (Individual Total × discount_amount / 100)

### Fixed Discount
- Individual item total is calculated
- Fixed discount amount (`discount_amount`) is subtracted
- Final price = Individual Total - discount_amount

### Dynamic Pricing
- Price calculated based on selected items
- Each item can have `price_override` or `discount_amount`
- Final price = Sum of (item prices × quantities)

## Inventory Management

### Component-based
- Stock availability based on individual products
- Minimum available stock from required items
- Automatically checks all required items for availability

### Independent
- Bundle has its own stock level
- Stock is managed separately from component products
- Useful for pre-packaged bundles

### Unlimited
- No stock tracking
- Always available
- Useful for digital bundles or services

## Cart Integration

When a bundle is added to cart:
1. Main bundle product is added as a cart line (if bundle has associated product)
2. Individual items are added as separate cart lines
3. Cart line meta includes `bundle_id` and `bundle_item_id` for tracking
4. Bundle discount can be applied at checkout level

## Frontend Components

### Bundle Index
- `resources/views/frontend/bundles/index.blade.php`
- Displays grid of available bundles
- Shows bundle image, name, price, savings

### Bundle Show
- `resources/views/frontend/bundles/show.blade.php`
- Displays bundle details
- Shows individual items
- Customization interface (if enabled)
- Add to cart form

## Best Practices

1. **Bundle Structure**
   - Keep bundles focused (3-5 items recommended)
   - Use required items for core bundle
   - Use optional items for upsells

2. **Pricing**
   - Set attractive but profitable discounts
   - Show savings to encourage purchase
   - Use price tiers for bulk purchases

3. **Inventory**
   - Use component-based for most bundles
   - Use independent for pre-packaged items
   - Monitor stock levels regularly

4. **Customization**
   - Enable for bundles with many optional items
   - Set clear min/max quantities
   - Provide helpful item descriptions

## Future Enhancements

1. Bundle recommendations
2. Bundle analytics (conversion rates, popular combinations)
3. Automated bundle creation based on purchase patterns
4. Bundle templates
5. Seasonal/time-limited bundles
6. Bundle subscriptions


