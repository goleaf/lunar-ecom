# Homepage Featured Collections System

## Overview

A comprehensive homepage system displaying featured collections with hero images, collection cards, promotional banners, lazy loading, and responsive design.

## Features

### Core Features

1. **Hero Section**
   - Sliding hero images with featured collections
   - Automatic rotation
   - Manual navigation (arrows and dots)
   - Keyboard navigation support
   - Responsive images with lazy loading

2. **Featured Collections**
   - Collection cards with images
   - Product count display
   - Collection badges (Hot, New, Featured)
   - Hover effects
   - Responsive grid layout

3. **Promotional Banners**
   - Top position banners (2-column grid)
   - Middle position banners (full-width)
   - Customizable links (collection, product, category, URL)
   - Time-based visibility
   - Responsive images

4. **Product Sections**
   - Bestsellers section
   - New Arrivals section
   - Product cards with lazy loading
   - "View All" links

5. **Performance Optimizations**
   - Lazy loading for images
   - Responsive images with srcset
   - Intersection Observer for fade-in animations
   - Smooth scrolling
   - CSS animations

## Models

### PromotionalBanner
- **Location**: `app/Models/PromotionalBanner.php`
- **Table**: `lunar_promotional_banners`
- **Key Fields**:
  - `title`, `subtitle`, `description`
  - `position`: top, middle, bottom
  - `order`: Display order
  - `is_active`: Active status
  - `link`, `link_text`, `link_type`: Link configuration
  - `display_conditions`: JSON conditions
  - `starts_at`, `ends_at`: Time-based visibility
- **Media**: Uses Spatie Media Library for banner images
- **Conversions**: desktop (1920x600), tablet (1024x400), mobile (768x300)

## Controllers

## Livewire Page

### Frontend\Pages\Homepage
- **Location**: `app/Livewire/Frontend/Pages/Homepage.php`
- **Responsibilities**:
  - Load featured collections (including hero subset)
  - Load bestsellers + new arrivals collections
  - Load promotional banners with link resolution
  - Provide SEO meta tags via `App\Services\SEOService`

## Views

### Homepage View (Livewire)
- **Location**: `resources/views/livewire/frontend/pages/homepage.blade.php`
- **Meta partial**: `resources/views/frontend/homepage/_meta.blade.php`
- **Sections**:
  - Hero slider (featured collections with hero-capable media)
  - Promotional banners (top)
  - Featured collections grid
  - Bestsellers section
  - Promotional banner(s) (middle)
  - New Arrivals section

### Collection Card Component
- **Location**:
  - `resources/views/components/frontend/collection-card.blade.php` (Blade component)
  - `resources/views/frontend/collection-card.blade.php` (markup include)
- **Features**:
  - Collection image with fallback
  - Collection name and description
  - Product count
  - Collection type badge
  - Hover effects

## Assets

### CSS
- Homepage styling is implemented with Tailwind utility classes in the Blade view.

### JavaScript
- **Location**: `resources/js/homepage.js`
- **Classes**:
  - `HeroSlider`: Manages hero image slider
- **Features**:
  - Auto-play hero slider
  - Manual navigation
  - Keyboard support
  - Respects `prefers-reduced-motion`

## Routes

```php
// Homepage
Route::get('/', \App\Livewire\Frontend\Pages\Homepage::class)->name('frontend.homepage');
```

## Usage Examples

### Creating Promotional Banner
```php
use App\Models\PromotionalBanner;

$banner = PromotionalBanner::create([
    'title' => 'Summer Sale',
    'subtitle' => 'Up to 50% Off',
    'description' => 'Shop the best deals on summer essentials',
    'position' => 'top',
    'order' => 1,
    'link_type' => 'collection',
    'link' => 'summer-sale',
    'link_text' => 'Shop Now',
    'is_active' => true,
]);

// Add banner image
$banner->addMediaFromUrl('https://example.com/banner.jpg')
    ->toMediaCollection('banners');
```

### Setting Up Featured Collections
```php
use App\Models\Collection;

// Mark collection as featured (show on homepage)
$collection = Collection::find(1);
$collection->update([
    'show_on_homepage' => true,
    'homepage_position' => 1,
]);

// Add hero image to collection
$collection->addMediaFromUrl('https://example.com/hero.jpg')
    ->toMediaCollection('hero');
```

## Homepage Structure

```
Homepage
├── Hero Section (Slider)
│   ├── Featured Collection 1
│   ├── Featured Collection 2
│   └── Featured Collection 3
├── Promotional Banners (Top)
│   ├── Banner 1
│   └── Banner 2
├── Featured Collections Grid
│   ├── Collection Card 1
│   ├── Collection Card 2
│   └── Collection Card 3
├── Bestsellers Section
│   ├── Product Card 1
│   ├── Product Card 2
│   └── ... (8 products)
├── Promotional Banner (Middle)
│   └── Full-width Banner
└── New Arrivals Section
    ├── Product Card 1
    ├── Product Card 2
    └── ... (8 products)
```

## Performance Optimizations

### Lazy Loading
- Images use `loading="lazy"` attribute
- IntersectionObserver for advanced lazy loading
- Progressive image loading with placeholders

### Responsive Images
- Multiple image sizes (desktop, tablet, mobile)
- srcset and sizes attributes
- WebP format support

### Animations
- CSS-based animations for performance
- Fade-in on scroll using IntersectionObserver
- Smooth transitions

### Caching
- Collection queries are optimized
- Product counts are cached
- Banner queries use indexes

## Responsive Design

### Breakpoints
- **Mobile**: < 640px
- **Tablet**: 641px - 1024px
- **Desktop**: > 1024px

### Adaptations
- Hero height: 400px (mobile), 500px (tablet), 600-800px (desktop)
- Grid columns: 1 (mobile), 2 (tablet), 3-4 (desktop)
- Font sizes scale down on mobile
- Navigation dots and arrows adjust size

## Best Practices

1. **Hero Images**
   - Use high-quality images (1920x600 recommended)
   - Optimize images before upload
   - Keep text readable over images
   - Limit to 3-5 hero slides

2. **Collections**
   - Show 3-6 featured collections on homepage
   - Ensure collection images are optimized
   - Keep product counts updated
   - Use descriptive collection names

3. **Banners**
   - Limit banners to prevent clutter
   - Use clear call-to-action text
   - Test banner links
   - Set expiration dates for time-sensitive banners

4. **Performance**
   - Optimize all images
   - Use lazy loading for below-fold content
   - Minimize JavaScript
   - Cache collection queries

5. **Accessibility**
   - Include alt text for all images
   - Ensure keyboard navigation works
   - Maintain color contrast
   - Test with screen readers

## Future Enhancements

1. **A/B Testing**
   - Test different hero images
   - Test banner placements
   - Track conversion rates

2. **Personalization**
   - Show personalized collections
   - Customer-specific banners
   - Recently viewed products

3. **Analytics**
   - Track hero slide engagement
   - Banner click rates
   - Collection click rates
   - Scroll depth

4. **Dynamic Content**
   - Real-time inventory updates
   - Flash sale countdowns
   - Weather-based collections

5. **Video Support**
   - Video hero backgrounds
   - Video banners
   - Product video previews


