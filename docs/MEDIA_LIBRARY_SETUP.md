# Media Library Integration

This document describes the comprehensive media library integration with Lunar for product images, collection images, and brand logos.

## Overview

The media library system provides:
- **Image Uploads**: Multiple image uploads with drag-and-drop functionality
- **Image Conversions**: Automatic generation of multiple image sizes (thumb, medium, large, xlarge)
- **Responsive Images**: Srcset and sizes attributes for optimal loading
- **Image Optimization**: Automatic optimization with quality settings
- **Brand Logos**: Dedicated logo collection for brands
- **Media Management**: Upload, delete, and reorder images via API

## Setup

### 1. Publish Media Library Configuration (Optional)

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"
```

### 2. Create Storage Link

Ensure the storage link is created:

```bash
php artisan storage:link
```

### 3. Configure Image Optimization

The system uses Spatie Image for optimization. Ensure you have the required image processing library:

- **GD** (built into PHP) - Basic support
- **Imagick** (recommended) - Better quality and performance

Install Imagick (if not already installed):
```bash
# Ubuntu/Debian
sudo apt-get install php-imagick

# macOS
brew install imagemagick
```

## Image Conversions

### Product & Collection Conversions

The following conversions are automatically generated:

| Conversion | Size | Quality | Use Case |
|------------|------|---------|----------|
| `small` | 300x300 | 85% | Admin thumbnails |
| `thumb` | 400x400 | 85% | Product cards, listings |
| `medium` | 800x800 | 90% | Product detail pages |
| `large` | 1200x1200 | 92% | Zoom/lightbox |
| `xlarge` | 1920x1920 | 95% | High-DPI displays |

### Responsive Image Conversions

For responsive images (srcset):

| Conversion | Width | Quality |
|------------|-------|---------|
| `responsive_320` | 320px | 85% |
| `responsive_640` | 640px | 85% |
| `responsive_768` | 768px | 85% |
| `responsive_1024` | 1024px | 90% |
| `responsive_1280` | 1280px | 90% |
| `responsive_1920` | 1920px | 92% |

### Brand Logo Conversions

| Conversion | Size | Quality | Use Case |
|------------|------|---------|----------|
| `small` | 100x100 | 90% | Thumbnails |
| `thumb` | 200x200 | 90% | Brand cards |
| `large` | 400x400 | 95% | Brand pages |

## Usage

### Uploading Images

#### Via API

**Upload Product Images:**
```javascript
const formData = new FormData();
formData.append('images[0]', file1);
formData.append('images[1]', file2);

fetch('/media/product/1/upload', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: formData
});
```

**Upload Collection Images:**
```javascript
fetch('/media/collection/1/upload', {
    method: 'POST',
    body: formData
});
```

**Upload Brand Logo:**
```javascript
const formData = new FormData();
formData.append('logo', file);

fetch('/media/brand/1/logo', {
    method: 'POST',
    body: formData
});
```

#### Via PHP

```php
use Lunar\Models\Product;
use App\Lunar\Media\MediaHelper;

$product = Product::find(1);

// Single image
MediaHelper::addImage($product, $request->file('image'));

// Multiple images
MediaHelper::addMultipleImages($product, $request->file('images'));
```

### Using Responsive Images

#### In Blade Templates

```blade
@php
    $firstMedia = $product->getFirstMedia('images');
@endphp

@include('frontend.components.responsive-image', [
    'media' => $firstMedia,
    'model' => $product,
    'collectionName' => 'images',
    'conversion' => 'medium',
    'sizeType' => 'product_card',
    'alt' => $product->translateAttribute('name'),
    'class' => 'w-full h-48 object-cover'
])
```

#### Manual Implementation

```blade
@php
    use App\Lunar\Media\MediaHelper;
    $media = $product->getFirstMedia('images');
    $attrs = MediaHelper::getResponsiveAttributes($media, 'product_card');
@endphp

<img 
    src="{{ $media->getUrl('medium') }}"
    srcset="{{ $attrs['srcset'] }}"
    sizes="{{ $attrs['sizes'] }}"
    alt="Product image"
    loading="lazy"
>
```

### Drag-and-Drop Upload Component

Include the uploader component in your views:

```blade
@include('frontend.components.image-uploader', [
    'modelId' => $product->id,
    'modelType' => 'product',
    'collectionName' => 'images'
])
```

The component provides:
- Drag-and-drop file upload
- Multiple file selection
- Upload progress indicator
- Image preview with delete option
- Automatic API integration

### Getting Image URLs

```php
use App\Lunar\Media\MediaHelper;
use Lunar\Models\Product;

$product = Product::find(1);

// Get first image URL
$url = MediaHelper::getFirstImageUrl($product, 'images', 'thumb');

// Get all image URLs
$urls = MediaHelper::getAllImageUrls($product, 'images', 'medium');

// Get responsive srcset
$media = $product->getFirstMedia('images');
$srcset = MediaHelper::getResponsiveSrcset($media);
```

## API Endpoints

### Upload Endpoints

**POST** `/media/product/{productId}/upload`
- Upload multiple product images
- Body: `images[]` (array of files)
- Returns: Array of uploaded media objects

**POST** `/media/collection/{collectionId}/upload`
- Upload multiple collection images
- Body: `images[]` (array of files)
- Returns: Array of uploaded media objects

**POST** `/media/brand/{brandId}/logo`
- Upload brand logo (single file)
- Body: `logo` (file)
- Returns: Uploaded media object

### Management Endpoints

**DELETE** `/media/{modelType}/{modelId}/{mediaId}`
- Delete a media item
- Model types: `product`, `collection`, `brand`

**POST** `/media/{modelType}/{modelId}/reorder`
- Reorder media items
- Body: `media_ids` (array of IDs in desired order)

## Configuration

### Media Configuration (`config/lunar/media.php`)

```php
return [
    'definitions' => [
        \Lunar\Models\Product::class => CustomMediaDefinitions::class,
        \Lunar\Models\Collection::class => CustomMediaDefinitions::class,
        \Lunar\Models\Brand::class => BrandMediaDefinitions::class,
    ],
    
    'collection' => 'images',
    
    'fallback' => [
        'url' => env('FALLBACK_IMAGE_URL', null),
        'path' => env('FALLBACK_IMAGE_PATH', null),
    ],
    
    'optimization' => [
        'enabled' => env('MEDIA_OPTIMIZATION_ENABLED', true),
        'quality' => [
            'thumb' => 85,
            'medium' => 90,
            'large' => 92,
            'xlarge' => 95,
        ],
    ],
    
    'responsive' => [
        'breakpoints' => [320, 640, 768, 1024, 1280, 1920],
        'sizes' => [
            'default' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw',
            'product_card' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw',
            'product_detail' => '(max-width: 768px) 100vw, 50vw',
        ],
    ],
];
```

### Environment Variables

```env
# Fallback image URL (shown when no image is available)
FALLBACK_IMAGE_URL=https://example.com/placeholder.jpg

# Media optimization
MEDIA_OPTIMIZATION_ENABLED=true
MEDIA_MAX_WIDTH=1920
MEDIA_MAX_HEIGHT=1920
```

## Image Optimization

### Automatic Optimization

Images are automatically optimized when conversions are generated:
- **Quality settings**: Configurable per conversion size
- **Format conversion**: JPEG for photos, PNG preserved for logos
- **Sharpening**: Applied for better visual quality
- **File size reduction**: Optimized compression

### Regenerating Conversions

To regenerate all image conversions (e.g., after changing quality settings):

```bash
php artisan media-library:regenerate
```

This creates queue jobs for each media item. Ensure your queue worker is running:

```bash
php artisan queue:work
```

## Responsive Images

### How It Works

Responsive images use the `srcset` and `sizes` attributes to:
1. Provide multiple image sizes to the browser
2. Let the browser choose the best size based on viewport
3. Reduce bandwidth on mobile devices
4. Provide high-quality images on high-DPI displays

### Size Types

- **`default`**: General purpose responsive images
- **`product_card`**: Optimized for product card grids
- **`product_detail`**: Optimized for product detail pages

### Custom Size Types

Add custom size types in `config/lunar/media.php`:

```php
'responsive' => [
    'sizes' => [
        'my_custom_size' => '(max-width: 480px) 100vw, 50vw',
    ],
],
```

## Files Created/Modified

### New Files
- `app/Lunar/Media/BrandMediaDefinitions.php` - Brand logo media definitions
- `app/Http/Controllers/Storefront/MediaController.php` - Media upload/management API
- `resources/views/storefront/components/image-uploader.blade.php` - Drag-and-drop upload component
- `resources/views/storefront/components/responsive-image.blade.php` - Responsive image component

### Modified Files
- `app/Lunar/Media/CustomMediaDefinitions.php` - Enhanced with responsive conversions and optimization
- `app/Lunar/Media/MediaHelper.php` - Added responsive image and multiple upload methods
- `config/lunar/media.php` - Added optimization and responsive image configuration
- `routes/web.php` - Added media upload routes
- `resources/views/storefront/products/_product-card.blade.php` - Updated to use responsive images
- `resources/views/storefront/products/show.blade.php` - Updated to use responsive images

## Best Practices

1. **Use Appropriate Conversions**: Use `thumb` for listings, `medium` for detail pages, `large` for zoom
2. **Lazy Loading**: Always use `loading="lazy"` for images below the fold
3. **Responsive Images**: Use the responsive image component for better performance
4. **Image Optimization**: Keep optimization enabled for production
5. **File Size Limits**: Set appropriate max file sizes in validation
6. **Format Selection**: Use JPEG for photos, PNG/SVG for logos
7. **Alt Text**: Always provide meaningful alt text for accessibility

## Troubleshooting

### Images Not Generating Conversions

1. Check queue worker is running: `php artisan queue:work`
2. Check disk permissions: `storage/app/public` must be writable
3. Verify Imagick/GD is installed and working
4. Check logs: `storage/logs/laravel.log`

### Responsive Images Not Working

1. Verify conversions are generated: Check `generated_conversions` in media table
2. Regenerate conversions: `php artisan media-library:regenerate`
3. Check browser console for srcset errors

### Upload Failures

1. Check file size limits (default: 10MB for products, 5MB for logos)
2. Verify CSRF token is included
3. Check disk space availability
4. Review validation rules in MediaController


