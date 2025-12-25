# Variant Media & Assets

Complete variant media and assets system for visual differentiation.

## Overview

Comprehensive media system for variants with support for:
1. **Variant-specific image gallery**
2. **Variant-specific videos**
3. **360° images**
4. **3D models / AR files**
5. **Fallback to product images**
6. **Media per channel**
7. **Media per locale**
8. **Sort order per variant**
9. **Alt text & accessibility metadata**

## Media Types

### Supported Media Types

- **`image`** - Standard images (JPG, PNG, WebP, etc.)
- **`video`** - Videos (MP4, WebM, etc.)
- **`image_360`** - 360° panoramic images
- **`model_3d`** - 3D model files (GLB, GLTF, OBJ, etc.)
- **`ar_file`** - AR files (USDZ, GLB for AR)
- **`document`** - Documents (PDF, etc.)

## Database Structure

### Enhanced Pivot Table

The `media_product_variant` table includes:

- `media_id` - Spatie Media Library media ID
- `product_variant_id` - Variant ID
- `media_type` - Type of media (image, video, image_360, model_3d, ar_file, document)
- `channel_id` - Channel-specific media (nullable)
- `locale` - Locale-specific media (nullable)
- `primary` - Primary media flag
- `position` - Sort order
- `alt_text` - Translatable alt text (JSON)
- `caption` - Translatable caption (JSON)
- `accessibility_metadata` - Accessibility metadata (JSON)
- `media_metadata` - Media-specific metadata (JSON)

## Usage

### VariantMediaService

```php
use App\Services\VariantMediaService;

$service = app(VariantMediaService::class);
```

### Get Media

```php
// Get all media for variant
$media = $service->getMedia($variant, [
    'channel_id' => 1,
    'locale' => 'en',
    'media_type' => 'image',
    'include_fallback' => true, // Fallback to product images
]);

// Get images
$images = $service->getImages($variant, [
    'channel_id' => 1,
    'locale' => 'en',
]);

// Get videos
$videos = $service->getVideos($variant);

// Get 360° images
$images360 = $service->get360Images($variant);

// Get 3D models
$models3d = $service->get3DModels($variant);

// Get AR files
$arFiles = $service->getARFiles($variant);
```

### Get Primary Image

```php
// Get primary image
$primaryImage = $service->getPrimaryImage($variant, $channelId, $locale);

// With fallback to product image
if (!$primaryImage) {
    $productImage = $variant->product->getFirstMedia('images');
}
```

### Attach Media

```php
// Attach image
$variantMedia = $service->attachMedia($variant, $mediaId, [
    'media_type' => 'image',
    'primary' => true,
    'channel_id' => 1,
    'locale' => 'en',
    'position' => 1,
    'alt_text' => [
        'en' => 'Red T-Shirt - Front View',
        'fr' => 'T-Shirt Rouge - Vue de Face',
    ],
    'caption' => [
        'en' => 'High-quality cotton t-shirt',
        'fr' => 'T-shirt en coton de haute qualité',
    ],
    'accessibility_metadata' => [
        'aria_label' => 'Product image showing red t-shirt front view',
        'longdesc' => 'A red cotton t-shirt displayed on a white background',
    ],
]);

// Attach video
$service->attachMedia($variant, $videoMediaId, [
    'media_type' => 'video',
    'position' => 2,
]);

// Attach 360° image
$service->attachMedia($variant, $image360MediaId, [
    'media_type' => 'image_360',
    'position' => 3,
]);

// Attach 3D model
$service->attachMedia($variant, $model3dMediaId, [
    'media_type' => 'model_3d',
    'media_metadata' => [
        'format' => 'glb',
        'scale' => 1.0,
        'rotation' => [0, 0, 0],
    ],
]);

// Attach AR file
$service->attachMedia($variant, $arFileMediaId, [
    'media_type' => 'ar_file',
    'media_metadata' => [
        'format' => 'usdz',
        'ios_ar' => true,
    ],
]);
```

### Detach Media

```php
// Detach media
$service->detachMedia($variant, $mediaId);
```

### Set Primary Media

```php
// Set primary image
$service->setPrimaryMedia($variant, $mediaId, 'image');

// Set primary video
$service->setPrimaryMedia($variant, $videoMediaId, 'video');
```

### Reorder Media

```php
// Reorder media (array of media IDs in desired order)
$service->reorderMedia($variant, [$mediaId1, $mediaId2, $mediaId3]);
```

### Get Media Gallery

```php
// Get complete media gallery
$gallery = $service->getMediaGallery($variant, [
    'channel_id' => 1,
    'locale' => 'en',
]);

// Returns:
// [
//     'images' => Collection,
//     'videos' => Collection,
//     'images_360' => Collection,
//     'models_3d' => Collection,
//     'ar_files' => Collection,
//     'primary_image' => VariantMedia|null,
// ]
```

### Bulk Attach Media

```php
// Bulk attach media
$service->bulkAttachMedia($variant, [
    [
        'media_id' => $image1Id,
        'options' => [
            'media_type' => 'image',
            'primary' => true,
            'position' => 1,
        ],
    ],
    [
        'media_id' => $image2Id,
        'options' => [
            'media_type' => 'image',
            'position' => 2,
        ],
    ],
    [
        'media_id' => $videoId,
        'options' => [
            'media_type' => 'video',
            'position' => 3,
        ],
    ],
]);
```

### Update Media Metadata

```php
// Update media metadata
$service->updateMediaMetadata($variant, $mediaId, [
    'alt_text' => [
        'en' => 'Updated alt text',
    ],
    'caption' => [
        'en' => 'Updated caption',
    ],
    'accessibility_metadata' => [
        'aria_label' => 'Updated aria label',
    ],
]);
```

### Sync Media

```php
// Replace all media
$service->syncMedia($variant, [
    [
        'media_id' => $newImageId,
        'options' => [
            'media_type' => 'image',
            'primary' => true,
        ],
    ],
]);
```

## Model Methods

### ProductVariant Methods

```php
// Get media gallery
$gallery = $variant->getMediaGallery([
    'channel_id' => 1,
    'locale' => 'en',
]);

// Get images
$images = $variant->getImages(['channel_id' => 1]);

// Get videos
$videos = $variant->getVideos();

// Get 360° images
$images360 = $variant->get360Images();

// Get 3D models
$models3d = $variant->get3DModels();

// Get AR files
$arFiles = $variant->getARFiles();

// Get primary image
$primaryImage = $variant->getPrimaryImage($channelId, $locale);

// Get thumbnail URL
$thumbnailUrl = $variant->getThumbnailUrl('thumb', $channelId, $locale);

// Attach media
$variantMedia = $variant->attachMedia($mediaId, [
    'media_type' => 'image',
    'primary' => true,
]);

// Detach media
$variant->detachMedia($mediaId);

// Set primary image
$variant->setPrimaryImage($mediaId, 'image');

// Reorder media
$variant->reorderMedia([$mediaId1, $mediaId2, $mediaId3]);
```

## Channel-Specific Media

```php
// Attach media for specific channel
$service->attachMedia($variant, $mediaId, [
    'media_type' => 'image',
    'channel_id' => 1, // Web channel
]);

// Get media for channel
$media = $service->getMedia($variant, [
    'channel_id' => 1,
]);

// Channel-specific media takes precedence over global media
// Falls back to global media if channel-specific not found
```

## Locale-Specific Media

```php
// Attach media for specific locale
$service->attachMedia($variant, $mediaId, [
    'media_type' => 'image',
    'locale' => 'fr',
    'alt_text' => [
        'fr' => 'T-shirt Rouge',
    ],
]);

// Get media for locale
$media = $service->getMedia($variant, [
    'locale' => 'fr',
]);

// Locale-specific media takes precedence over global media
// Falls back to global media if locale-specific not found
```

## Fallback to Product Images

```php
// Get media with fallback
$media = $service->getMedia($variant, [
    'include_fallback' => true, // Default: true
]);

// If variant has no media, returns product media
// Fallback media is marked with 'is_fallback' => true
```

## Alt Text & Accessibility

```php
// Attach media with alt text
$service->attachMedia($variant, $mediaId, [
    'media_type' => 'image',
    'alt_text' => [
        'en' => 'Red T-Shirt - Front View',
        'fr' => 'T-Shirt Rouge - Vue de Face',
    ],
    'caption' => [
        'en' => 'High-quality cotton t-shirt',
        'fr' => 'T-shirt en coton de haute qualité',
    ],
    'accessibility_metadata' => [
        'aria_label' => 'Product image showing red t-shirt front view',
        'longdesc' => 'A red cotton t-shirt displayed on a white background',
        'role' => 'img',
    ],
]);

// Get alt text for locale
$altText = $variantMedia->getAltText('en');
$caption = $variantMedia->getCaption('en');
```

## Sort Order

```php
// Media is automatically ordered by position
$media = $service->getMedia($variant);
// Returns ordered by position

// Reorder media
$service->reorderMedia($variant, [$mediaId1, $mediaId2, $mediaId3]);
// Updates positions: 1, 2, 3

// Set position when attaching
$service->attachMedia($variant, $mediaId, [
    'position' => 5, // Specific position
]);
```

## 3D Models & AR Files

```php
// Attach 3D model
$service->attachMedia($variant, $modelMediaId, [
    'media_type' => 'model_3d',
    'media_metadata' => [
        'format' => 'glb',
        'scale' => 1.0,
        'rotation' => [0, 0, 0],
        'animation' => 'idle',
    ],
]);

// Attach AR file
$service->attachMedia($variant, $arMediaId, [
    'media_type' => 'ar_file',
    'media_metadata' => [
        'format' => 'usdz',
        'ios_ar' => true,
        'android_ar' => false,
    ],
]);

// Get 3D models
$models = $variant->get3DModels();

// Get AR files
$arFiles = $variant->getARFiles();
```

## Frontend Usage

### Display Media Gallery

```blade
@php
    $gallery = $variant->getMediaGallery([
        'channel_id' => $currentChannel->id,
        'locale' => app()->getLocale(),
    ]);
@endphp

<!-- Images -->
@foreach($gallery['images'] as $image)
    <img 
        src="{{ $image['url'] }}" 
        alt="{{ $image['alt_text'] }}"
        data-caption="{{ $image['caption'] }}"
    >
@endforeach

<!-- Videos -->
@foreach($gallery['videos'] as $video)
    <video src="{{ $video['url'] }}" controls>
        {{ $video['caption'] }}
    </video>
@endforeach

<!-- 360° Images -->
@foreach($gallery['images_360'] as $image360)
    <div class="viewer-360" data-image="{{ $image360['url'] }}">
        <!-- 360° viewer -->
    </div>
@endforeach

<!-- 3D Models -->
@foreach($gallery['models_3d'] as $model)
    <model-viewer 
        src="{{ $model['url'] }}"
        alt="{{ $model['alt_text'] }}"
        auto-rotate
        camera-controls
    ></model-viewer>
@endforeach

<!-- AR Files -->
@foreach($gallery['ar_files'] as $arFile)
    <a 
        rel="ar" 
        href="{{ $arFile['url'] }}"
        aria-label="{{ $arFile['alt_text'] }}"
    >
        <img src="{{ $arFile['thumb_url'] }}" alt="{{ $arFile['alt_text'] }}">
    </a>
@endforeach
```

## Best Practices

1. **Always set alt text** for accessibility
2. **Use channel-specific media** for different sales channels
3. **Use locale-specific media** for internationalization
4. **Set primary image** for each variant
5. **Order media** by importance (position)
6. **Use fallback** to product images when variant has no media
7. **Include accessibility metadata** for screen readers
8. **Optimize media** for web (compression, formats)
9. **Use appropriate media types** (image, video, 3D, AR)
10. **Cache media URLs** for performance

## Notes

- **Media storage**: Uses Spatie MediaLibrary
- **Fallback**: Automatically falls back to product images
- **Channel priority**: Channel-specific media > global media
- **Locale priority**: Locale-specific media > global media
- **Sort order**: Media ordered by position field
- **Primary media**: One primary media per type
- **Accessibility**: Alt text and metadata for screen readers
- **3D/AR**: Supports GLB, GLTF, USDZ formats

