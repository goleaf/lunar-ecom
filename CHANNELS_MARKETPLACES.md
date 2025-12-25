# Channels & Marketplaces System

This document describes the comprehensive multi-channel and marketplace system that supports:

1. **Multi-store support** - Multiple sales channels
2. **Channel-specific prices** - Different pricing per channel
3. **Channel-specific visibility** - Control product visibility per channel
4. **Channel-specific descriptions** - Different content per channel
5. **Channel-specific media** - Different images/media per channel
6. **Marketplace-ready structure** - Ready for Amazon, eBay, Etsy, etc.
7. **Geo-restricted availability** - Country/region-based restrictions

## Overview

The system extends Lunar's built-in channel support with advanced features for multi-channel and marketplace operations.

## Database Structure

### Channels Table Extensions

```sql
-- Marketplace fields
marketplace_type VARCHAR(255) NULL
marketplace_config JSON NULL
default_currency_id BIGINT NULL (FK to currencies)
default_language_id BIGINT NULL (FK to languages)

-- Geo-restrictions
allowed_countries JSON NULL
blocked_countries JSON NULL
allowed_regions JSON NULL

-- Channel settings
is_active BOOLEAN DEFAULT TRUE
sync_enabled BOOLEAN DEFAULT FALSE
last_synced_at TIMESTAMP NULL
```

### Channel Product Data Table

Stores channel-specific product information:

```sql
product_id BIGINT (FK to products)
channel_id BIGINT (FK to channels)
visibility ENUM('public', 'private', 'scheduled')
is_visible BOOLEAN
published_at TIMESTAMP NULL
scheduled_publish_at TIMESTAMP NULL
scheduled_unpublish_at TIMESTAMP NULL

-- Descriptions
short_description TEXT NULL
full_description LONGTEXT NULL
technical_description LONGTEXT NULL

-- SEO
meta_title VARCHAR(255) NULL
meta_description TEXT NULL
meta_keywords TEXT NULL

-- Geo-restrictions
allowed_countries JSON NULL
blocked_countries JSON NULL
allowed_regions JSON NULL
```

### Channel Media Table

Links Spatie Media Library media to channels:

```sql
channel_id BIGINT (FK to channels)
mediable_type VARCHAR(255) -- Product, Collection, Brand, etc.
mediable_id BIGINT
media_id BIGINT -- Spatie Media Library media ID
collection_name VARCHAR(255)
position INT
is_primary BOOLEAN
alt_text JSON NULL
caption JSON NULL
```

## Services

### ChannelProductService

Manages channel-specific product data.

**Location**: `app/Services/ChannelProductService.php`

**Key Methods**:

```php
use App\Services\ChannelProductService;

$service = app(ChannelProductService::class);

// Get or create channel data
$data = $service->getOrCreateChannelData($product, $channel);

// Get visibility
$visibility = $service->getVisibility($product, $channel);

// Set visibility
$service->setVisibility($product, $channel, 'public');

// Get description
$description = $service->getDescription($product, $channel, 'full');

// Set description
$service->setDescription($product, $channel, 'full', 'Product description');

// Get SEO fields
$seo = $service->getSEOFields($product, $channel);

// Set SEO fields
$service->setSEOFields($product, $channel, [
    'meta_title' => 'Title',
    'meta_description' => 'Description',
    'meta_keywords' => 'keywords',
]);

// Check visibility
$isVisible = $service->isVisible($product, $channel);

// Set geo-restrictions
$service->setGeoRestrictions(
    $product,
    $channel,
    ['US', 'CA'], // Allowed countries
    ['CN'],       // Blocked countries
    ['NA']        // Allowed regions
);
```

### ChannelMediaService

Manages channel-specific media assignments.

**Location**: `app/Services/ChannelMediaService.php`

**Key Methods**:

```php
use App\Services\ChannelMediaService;

$service = app(ChannelMediaService::class);

// Assign media to channel
$service->assignMedia($product, $channel, $mediaId, 'images', [
    'position' => 0,
    'is_primary' => true,
    'alt_text' => ['en' => 'Product image'],
    'caption' => ['en' => 'Main product image'],
]);

// Get media for channel
$media = $service->getMedia($product, $channel, 'images');

// Get primary media
$primaryMedia = $service->getPrimaryMedia($product, $channel, 'images');

// Set primary media
$service->setPrimaryMedia($product, $channel, $mediaId, 'images');

// Reorder media
$service->reorderMedia($product, $channel, [$mediaId1, $mediaId2, $mediaId3], 'images');

// Remove media
$service->removeMedia($product, $channel, $mediaId, 'images');
```

### GeoRestrictionService

Manages geo-restrictions for channels and products.

**Location**: `app/Services/GeoRestrictionService.php`

**Key Methods**:

```php
use App\Services\GeoRestrictionService;

$service = app(GeoRestrictionService::class);

// Check if country allowed for channel
$allowed = $service->isCountryAllowed($channel, 'US');

// Check if product available in country
$available = $service->isProductAvailableInCountry($product, $channel, 'US');

// Set channel restrictions
$service->setChannelRestrictions(
    $channel,
    ['US', 'CA'], // Allowed
    ['CN'],       // Blocked
    ['NA']        // Regions
);

// Get country from request
$countryCode = $service->getCountryFromRequest();
```

### MarketplaceService

Manages marketplace integrations and syncing.

**Location**: `app/Services/MarketplaceService.php`

**Key Methods**:

```php
use App\Services\MarketplaceService;

$service = app(MarketplaceService::class);

// Create marketplace channel
$amazonChannel = $service->createMarketplaceChannel(
    'Amazon US',
    'amazon-us',
    'amazon',
    [
        'api_key' => 'your-api-key',
        'api_secret' => 'your-secret',
        'marketplace_id' => 'ATVPDKIKX0DER',
    ]
);

// Sync product to marketplace
$success = $service->syncProductToMarketplace($product, $amazonChannel);

// Bulk sync
$result = $service->bulkSyncProducts($products, $amazonChannel);
// Returns: ['success' => 10, 'failed' => 2, 'total' => 12]

// Get sync status
$status = $service->getSyncStatus($amazonChannel);
```

## Product Model Extensions

The Product model includes channel-specific methods:

```php
use App\Models\Product;
use Lunar\Models\Channel;

$product = Product::find(1);
$channel = Channel::find(1);

// Get channel data
$channelData = $product->getChannelData($channel);

// Get channel visibility
$visibility = $product->getChannelVisibility($channel);

// Get channel description
$description = $product->getChannelDescription($channel, 'full');

// Get channel media
$media = $product->getChannelMedia($channel, 'images');

// Check visibility
$isVisible = $product->isVisibleInChannel($channel);

// Check geo-availability
$available = $product->isAvailableInCountry($channel, 'US');
```

## Channel Model Extensions

The Channel model includes marketplace support:

```php
use App\Models\Channel;

$channel = Channel::find(1);

// Check if marketplace
$isMarketplace = $channel->isMarketplace();

// Get marketplace config
$apiKey = $channel->getMarketplaceConfig('api_key');

// Set marketplace config
$channel->setMarketplaceConfig('api_key', 'new-key');
$channel->save();

// Scopes
$activeChannels = Channel::active()->get();
$marketplaces = Channel::marketplaces()->get();
$syncEnabled = Channel::syncEnabled()->get();
```

## Usage Examples

### Multi-Store Setup

```php
// Create webstore channel
$webstore = Channel::create([
    'name' => 'Web Store',
    'handle' => 'webstore',
    'default' => true,
    'marketplace_type' => 'webstore',
]);

// Create mobile app channel
$mobileApp = Channel::create([
    'name' => 'Mobile App',
    'handle' => 'mobile-app',
    'marketplace_type' => 'webstore',
]);

// Create Amazon channel
$amazon = app(MarketplaceService::class)->createMarketplaceChannel(
    'Amazon US',
    'amazon-us',
    'amazon',
    ['api_key' => '...', 'api_secret' => '...']
);
```

### Channel-Specific Pricing

```php
use Lunar\Models\ProductVariant;
use Lunar\Models\Price;

$variant = ProductVariant::find(1);
$webstore = Channel::find(1);
$mobileApp = Channel::find(2);

// Set price for webstore
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'channel_id' => $webstore->id,
    'currency_id' => 1,
    'price' => 10000, // $100.00
]);

// Set different price for mobile app
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'channel_id' => $mobileApp->id,
    'currency_id' => 1,
    'price' => 9500, // $95.00 (discount for app)
]);
```

### Channel-Specific Visibility

```php
use App\Services\ChannelProductService;

$service = app(ChannelProductService::class);

// Make product visible only on webstore
$service->setVisibility($product, $webstore, 'public');

// Hide product on mobile app
$service->setVisibility($product, $mobileApp, 'private');
```

### Channel-Specific Descriptions

```php
// Set different descriptions per channel
$service->setDescription($product, $webstore, 'full', 'Web store description');
$service->setDescription($product, $mobileApp, 'full', 'Mobile app description');
$service->setDescription($product, $amazon, 'full', 'Amazon listing description');
```

### Channel-Specific Media

```php
use App\Services\ChannelMediaService;

$mediaService = app(ChannelMediaService::class);

// Assign different images per channel
$webstoreImage = $product->addMediaFromUrl('https://example.com/web-image.jpg')
    ->toMediaCollection('images');
    
$mobileImage = $product->addMediaFromUrl('https://example.com/mobile-image.jpg')
    ->toMediaCollection('images');

$mediaService->assignMedia($product, $webstore, $webstoreImage->id, 'images', [
    'is_primary' => true,
]);

$mediaService->assignMedia($product, $mobileApp, $mobileImage->id, 'images', [
    'is_primary' => true,
]);
```

### Geo-Restrictions

```php
use App\Services\GeoRestrictionService;

$geoService = app(GeoRestrictionService::class);

// Restrict channel to specific countries
$geoService->setChannelRestrictions(
    $channel,
    ['US', 'CA', 'MX'], // Only allow North America
    null,
    ['NA']
);

// Restrict product in channel to specific countries
$channelProductService = app(ChannelProductService::class);
$channelProductService->setGeoRestrictions(
    $product,
    $channel,
    ['US', 'CA'], // Allowed
    ['CN'],       // Blocked
    null
);
```

### Marketplace Syncing

```php
use App\Services\MarketplaceService;

$marketplaceService = app(MarketplaceService::class);

// Enable sync for channel
$amazonChannel->update(['sync_enabled' => true]);

// Sync single product
$marketplaceService->syncProductToMarketplace($product, $amazonChannel);

// Bulk sync all products
$products = Product::published()->get();
$result = $marketplaceService->bulkSyncProducts($products, $amazonChannel);

echo "Synced: {$result['success']}, Failed: {$result['failed']}";
```

## Middleware

### GeoRestrictionMiddleware

Automatically enforces geo-restrictions based on user's country.

**Location**: `app/Http/Middleware/GeoRestrictionMiddleware.php`

**Usage**: Add to routes that need geo-restriction:

```php
Route::middleware([GeoRestrictionMiddleware::class])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});
```

## Marketplace Types

Supported marketplace types:

- `webstore` - Standard web store
- `amazon` - Amazon Marketplace
- `ebay` - eBay
- `etsy` - Etsy
- `shopify` - Shopify store
- `woocommerce` - WooCommerce store
- `custom` - Custom marketplace (extendable)

## Features

### Multi-Store Support

- Multiple sales channels (web, mobile, marketplace)
- Channel-specific configuration
- Independent product catalogs per channel

### Channel-Specific Prices

- Different pricing per channel
- Currency support per channel
- Customer group pricing per channel

### Channel-Specific Visibility

- Control product visibility per channel
- Scheduled publishing per channel
- Independent availability per channel

### Channel-Specific Content

- Different descriptions per channel
- Different SEO fields per channel
- Different media per channel

### Marketplace-Ready

- Pre-configured for major marketplaces
- Extensible for custom marketplaces
- Sync status tracking
- Bulk sync support

### Geo-Restrictions

- Country-level restrictions
- Region-level restrictions
- Product-level restrictions
- Channel-level restrictions
- Automatic detection from request headers

## Best Practices

1. **Default Channel**: Always set a default channel
2. **Fallback Values**: System falls back to product defaults if no channel-specific data
3. **Sync Scheduling**: Use queues for marketplace syncing
4. **Geo-Detection**: Use CDN headers for accurate country detection
5. **Media Optimization**: Optimize images per channel requirements
6. **Testing**: Test geo-restrictions with VPN/proxy
7. **Monitoring**: Monitor sync status and failures

## API Endpoints

### Channel Management

```php
// Get channel products
GET /api/channels/{channel}/products

// Get channel-specific product data
GET /api/products/{product}/channels/{channel}

// Update channel-specific data
PUT /api/products/{product}/channels/{channel}

// Sync to marketplace
POST /api/channels/{channel}/sync
```

## Notes

- Channel-specific data falls back to product defaults if not set
- Geo-restrictions are checked at both channel and product levels
- Marketplace syncing should be queued for large catalogs
- Media assignments don't duplicate files, just link existing media
- All prices support multi-currency per channel

