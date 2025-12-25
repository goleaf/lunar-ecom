# SEO Optimization Implementation

This document describes the comprehensive SEO features implemented for the Lunar e-commerce application.

## Features Implemented

### 1. Dynamic Meta Tags
- **Product Pages**: Full meta tag support including title, description, keywords, Open Graph, and Twitter Card tags
- **Category Pages**: Already had SEO support, now enhanced
- **Collection Pages**: Added meta tags for collections
- **Brand Pages**: Added meta tags for brand pages
- **Home/Index Pages**: Default meta tags for product listings and other index pages

### 2. Structured Data Markup (JSON-LD)
- **Product Rich Snippets**: Full Product schema with:
  - Product name, description, SKU, MPN
  - Brand information
  - Pricing and availability
  - Images
  - Aggregate ratings (if reviews exist)
  - Product dimensions and weight
  - Category information
- **Organization Schema**: Site-wide organization structured data
- **Website Schema**: Website structured data with search action
- **Brand Schema**: Brand structured data for brand pages
- **Breadcrumb Schema**: Breadcrumb navigation structured data

### 3. XML Sitemaps
- **Sitemap Index**: Main sitemap index that references all sub-sitemaps
- **Products Sitemap**: Paginated sitemap for all published products
- **Categories Sitemap**: Paginated sitemap for all active categories
- **Collections Sitemap**: Paginated sitemap for all collections
- **Static Pages Sitemap**: Sitemap for static pages (home, product index, collections index, etc.)
- **Automatic Pagination**: Handles large datasets with up to 10,000 URLs per sitemap file

### 4. Robots.txt
- **Dynamic robots.txt**: Generated dynamically via route `/robots.txt`
- **Static Fallback**: Static robots.txt file in public directory
- **Proper Directives**: 
  - Allows all search engines
  - Disallows admin, checkout, cart, API, and analytics pages
  - Disallows filtered/sorted URLs
  - Includes sitemap location
  - Supports specific bot rules

### 5. Canonical URLs
- **Product Pages**: Canonical URLs based on default URL slug
- **Category Pages**: Canonical URLs for category pages
- **Collection Pages**: Canonical URLs for collection pages
- **Brand Pages**: Canonical URLs for brand pages
- **All Pages**: Canonical URLs prevent duplicate content issues

### 6. Rich Snippets for Products
- **Product Schema**: Complete Product schema.org markup
- **Offer Schema**: Pricing and availability information
- **AggregateRating**: Review ratings and counts
- **Brand Information**: Brand name and details
- **Product Images**: Multiple product images
- **Product Properties**: Weight, dimensions, SKU, MPN

## File Structure

### Core SEO Classes
- `app/Lunar/Products/ProductSEO.php` - Product SEO helper
- `app/Lunar/Categories/CategorySEO.php` - Category SEO helper (enhanced)
- `app/Services/SEOService.php` - General SEO service

### Controllers
- `app/Http/Controllers/Storefront/SitemapController.php` - XML sitemap generation
- `app/Http/Controllers/Storefront/RobotsController.php` - Dynamic robots.txt

### Views
- `resources/views/sitemap/index.blade.php` - Sitemap index template
- `resources/views/sitemap/urlset.blade.php` - URL set template
- Updated all storefront views with SEO meta sections

### Routes
- `/sitemap/` - Main sitemap index
- `/sitemap/products-{page}` - Products sitemap
- `/sitemap/categories-{page}` - Categories sitemap
- `/sitemap/collections-{page}` - Collections sitemap
- `/sitemap/static.xml` - Static pages sitemap
- `/robots.txt` - Dynamic robots.txt

## Usage Examples

### Product SEO
```php
use App\Lunar\Products\ProductSEO;

// Get meta tags
$metaTags = ProductSEO::getMetaTags($product);

// Get structured data
$structuredData = ProductSEO::getStructuredData($product);

// Get robots meta
$robotsMeta = ProductSEO::getRobotsMeta($product);
```

### Category SEO
```php
use App\Lunar\Categories\CategorySEO;

// Get meta tags
$metaTags = CategorySEO::getMetaTags($category);

// Get structured data
$structuredData = CategorySEO::getStructuredData($category);
```

### General SEO
```php
use App\Services\SEOService;

// Get default meta tags
$metaTags = SEOService::getDefaultMetaTags(
    'Page Title',
    'Page description',
    'image-url.jpg',
    'canonical-url'
);

// Generate breadcrumb structured data
$breadcrumbData = SEOService::generateBreadcrumbStructuredData($breadcrumbs);
```

## View Implementation

All storefront views now include SEO meta tags:

```blade
@section('meta')
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    <link rel="canonical" href="{{ $metaTags['canonical'] }}">
    
    {{-- Structured Data --}}
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection
```

## Sitemap Access

- Main sitemap: `https://yourdomain.com/sitemap/`
- Products: `https://yourdomain.com/sitemap/products-1.xml`
- Categories: `https://yourdomain.com/sitemap/categories-1.xml`
- Collections: `https://yourdomain.com/sitemap/collections-1.xml`
- Static: `https://yourdomain.com/sitemap/static.xml`

## Robots.txt

Access at: `https://yourdomain.com/robots.txt`

The robots.txt file:
- Allows all search engines to crawl public pages
- Blocks admin, checkout, cart, and API endpoints
- Blocks filtered/sorted URLs to prevent duplicate content
- References the sitemap location

## SEO Best Practices Implemented

1. **Unique Titles**: Each page has a unique, descriptive title
2. **Meta Descriptions**: All pages have meta descriptions (auto-generated if not set)
3. **Canonical URLs**: Prevents duplicate content issues
4. **Structured Data**: Rich snippets for better search result display
5. **Sitemaps**: Complete XML sitemaps for all content
6. **Robots.txt**: Proper crawling directives
7. **Open Graph**: Social media sharing optimization
8. **Twitter Cards**: Twitter sharing optimization
9. **Mobile-Friendly**: Responsive meta tags
10. **Language Support**: Proper lang attribute in HTML

## Testing

To test the SEO implementation:

1. **Sitemaps**: Visit `/sitemap/` to see the sitemap index
2. **Robots.txt**: Visit `/robots.txt` to see the robots file
3. **Meta Tags**: View page source on any product/category page
4. **Structured Data**: Use Google's Rich Results Test tool
5. **Canonical URLs**: Check for `<link rel="canonical">` tags

## Future Enhancements

Potential improvements:
- Image sitemaps for product images
- News sitemaps (if adding blog/news)
- Video sitemaps (if adding product videos)
- FAQ structured data for product FAQs
- Review structured data enhancement
- Local business schema (if applicable)

## Notes

- All SEO data is generated dynamically
- Meta descriptions are auto-generated if not manually set
- Sitemaps are generated on-the-fly (consider caching for large sites)
- Structured data follows Schema.org standards
- All URLs are SEO-friendly using slugs

