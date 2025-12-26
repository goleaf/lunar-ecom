# Product Categories Hierarchy System

This document describes the nested product category system with unlimited depth, category trees, breadcrumbs, filters, SEO-friendly URLs, and category images.

## Overview

The category system provides:
- **Unlimited Depth**: Nested categories with no depth limit using nested set pattern
- **Category Trees**: Hierarchical category structures
- **Breadcrumbs**: Automatic breadcrumb generation for navigation
- **Category Filters**: Filter products by price, brand, and other attributes
- **SEO-Friendly URLs**: Full path URLs like `/categories/electronics/phones/smartphones`
- **Category Images**: Support for category images with multiple sizes
- **SEO Optimization**: Meta tags, structured data, and sitemap support

## Database Schema

The categories table uses the nested set pattern for efficient tree queries:

```php
Schema::create('lunar_categories', function (Blueprint $table) {
    $table->id();
    $table->nestedSet(); // _lft, _rgt, parent_id
    $table->json('name'); // Translatable
    $table->string('slug')->unique();
    $table->json('description')->nullable(); // Translatable
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    $table->integer('display_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('show_in_navigation')->default(true);
    $table->string('icon')->nullable();
    $table->unsignedInteger('product_count')->default(0);
    $table->timestamps();
    $table->softDeletes();
});
```

## Model Features

### Category Model

The `Category` model includes:

- **Nested Set Pattern**: Using `kalnoy/nestedset` package
- **Translatable Fields**: Name and description support multiple languages
- **Media Library**: Image support via Spatie Media Library
- **SEO Fields**: Meta title and description
- **Cached Counts**: Product count caching
- **Breadcrumb Generation**: Automatic breadcrumb paths

### Key Methods

```php
use App\Models\Category;

$category = Category::find(1);

// Get translatable name
$name = $category->getName(); // Current locale
$nameFr = $category->getName('fr'); // French

// Get description
$description = $category->getDescription();

// Get full path slug
$path = $category->getFullPath(); // "electronics/phones/smartphones"

// Get breadcrumb
$breadcrumb = $category->getBreadcrumb();

// Get ancestors (all parents)
$ancestors = $category->getAncestors();

// Get descendants (all children recursively)
$descendants = $category->getDescendants();

// Get children (direct children only)
$children = $category->getChildren();

// Get siblings
$siblings = $category->getSiblings();

// Get parent
$parent = $category->getParent();

// Get all products including from descendants
$products = $category->getAllProducts();

// Get image URL
$imageUrl = $category->getImageUrl('thumb');
```

## Creating Categories

### Basic Category Creation

```php
use App\Models\Category;

// Create root category
$electronics = Category::create([
    'name' => [
        'en' => 'Electronics',
        'fr' => 'Électronique',
    ],
    'description' => [
        'en' => 'Electronic products and gadgets',
        'fr' => 'Produits électroniques et gadgets',
    ],
    'meta_title' => 'Electronics - Shop Now',
    'meta_description' => 'Browse our wide selection of electronics',
    'is_active' => true,
    'show_in_navigation' => true,
]);
```

### Creating Nested Categories

```php
use App\Models\Category;
use App\Lunar\Categories\CategoryHelper;

// Create child category
$phones = CategoryHelper::create([
    'name' => ['en' => 'Phones'],
    'description' => ['en' => 'Mobile phones and smartphones'],
], $electronics);

// Or using nested set methods
$smartphones = Category::create([
    'name' => ['en' => 'Smartphones'],
]);
$phones->appendNode($smartphones);
```

### Using Helper Methods

```php
use App\Lunar\Categories\CategoryHelper;

// Create with parent
$category = CategoryHelper::create([
    'name' => ['en' => 'Tablets'],
    'description' => ['en' => 'Tablet computers'],
], $electronics);

// Move category
CategoryHelper::move($category, $newParent);

// Get category tree
$tree = CategoryHelper::getTree();

// Get navigation menu
$navigation = CategoryHelper::getNavigation(3); // Max depth 3
```

## Category Images

### Adding Images

```php
use App\Lunar\Media\MediaHelper;

$category = Category::find(1);

// Add image from file upload
MediaHelper::addImage($category, $request->file('image'), 'image');

// Or directly
$category->addMedia($request->file('image'))
    ->toMediaCollection('image');
```

### Image Conversions

The system provides multiple image sizes:

- `small`: 200x200px (category cards)
- `thumb`: 400x400px (category listings)
- `large`: 800x800px (category detail pages)
- `banner`: 1200x400px (category headers)

```php
$category = Category::find(1);

// Get different sizes
$small = $category->getImageUrl('small');
$thumb = $category->getImageUrl('thumb');
$large = $category->getImageUrl('large');
$banner = $category->getImageUrl('banner');
```

## SEO Features

### SEO Meta Tags

```php
use App\Lunar\Categories\CategorySEO;

$category = Category::find(1);

// Get all meta tags
$metaTags = CategorySEO::getMetaTags($category);
// Returns: title, description, keywords, og:title, og:description, og:image, etc.

// Get structured data (JSON-LD)
$structuredData = CategorySEO::getStructuredData($category);

// Get robots meta
$robots = CategorySEO::getRobotsMeta($category);
```

### SEO-Friendly URLs

Categories automatically generate SEO-friendly URLs:

- Root category: `/categories/electronics`
- Nested category: `/categories/electronics/phones`
- Deep nesting: `/categories/electronics/phones/smartphones`

The system supports full path resolution:

```php
use App\Lunar\Categories\CategoryHelper;

// Find by full path
$category = CategoryHelper::findByPath('electronics/phones/smartphones');

// Get URL
$url = CategoryHelper::getUrl($category);
```

## Category Trees

### Getting Category Tree

```php
use App\Repositories\CategoryRepository;

$repository = new CategoryRepository();

// Get all root categories
$roots = $repository->getRootCategories();

// Get tree from specific root
$tree = $repository->getCategoryTree($rootCategory, 5); // Max depth 5

// Get flat list with indentation
$flatList = $repository->getFlatList($rootCategory);
```

### Navigation Menu

```php
use App\Lunar\Categories\CategoryHelper;

// Get navigation menu (max depth 3)
$navigation = CategoryHelper::getNavigation(3);

// In Blade template
@include('storefront.components.category-tree', ['maxDepth' => 3])
```

## Breadcrumbs

### Automatic Breadcrumb Generation

```php
$category = Category::find(1);

// Get breadcrumb array
$breadcrumb = $category->getBreadcrumb();
// Returns: [
//     ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics', 'url' => '/categories/electronics'],
//     ['id' => 2, 'name' => 'Phones', 'slug' => 'phones', 'url' => '/categories/electronics/phones'],
//     ['id' => 3, 'name' => 'Smartphones', 'slug' => 'smartphones', 'url' => '/categories/electronics/phones/smartphones'],
// ]
```

### In Views

```blade
@if(isset($breadcrumb) && count($breadcrumb) > 0)
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            @foreach($breadcrumb as $crumb)
                <li>
                    @if(!$loop->last)
                        <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a> /
                    @else
                        <span>{{ $crumb['name'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
```

## Category Filters

### Product Filtering

The category controller automatically filters products by:

- **Price Range**: `min_price` and `max_price` query parameters
- **Brand**: `brand_id` query parameter
- **Sorting**: `sort` parameter (default, price_asc, price_desc, newest)

### Using Filter Service

```php
use App\Services\CategoryFilterService;

$filterService = new CategoryFilterService();
$query = Product::query();

// Filter by category (includes descendants)
$query = $filterService->filterByCategory($query, $category, true);

// Filter by multiple categories (AND logic)
$query = $filterService->filterByCategoriesAnd($query, [1, 2, 3]);

// Filter by multiple categories (OR logic)
$query = $filterService->filterByCategoriesOr($query, [1, 2, 3]);

// Exclude categories
$query = $filterService->excludeCategories($query, [4, 5]);
```

## Product-Category Relationship

### Associating Products with Categories

```php
use App\Models\Product;
use App\Models\Category;

$product = Product::find(1);
$category = Category::find(1);

// Attach product to category
$category->products()->attach($product->id, ['position' => 1]);

// Or using helper
$category->attachProducts([$product->id], ['position' => 1]);

// Attach multiple products
$category->attachProducts([1, 2, 3], ['position' => 0]);

// Detach products
$category->detachProducts([$product->id]);
```

### Getting Products

```php
$category = Category::find(1);

// Get products in this category only
$products = $category->products;

// Get all products including from descendant categories
$allProducts = $category->getAllProducts();

// Products relationship on Product model
$product = Product::find(1);
$categories = $product->categories;
```

## API Endpoints

### Category Endpoints

**GET** `/categories` - Get root categories

**GET** `/categories/tree` - Get category tree
- Query params: `root_id`, `depth`

**GET** `/categories/flat` - Get flat category list
- Query params: `root_id`

**GET** `/categories/navigation` - Get navigation categories
- Query params: `max_depth`

**GET** `/categories/{path}` - Get category by path
- Supports nested paths: `/categories/electronics/phones/smartphones`

**GET** `/categories/{category}/breadcrumb` - Get breadcrumb

### Category Show Page

The category show page supports filtering:

**GET** `/categories/{path}?min_price=10&max_price=100&brand_id=1&sort=price_asc`

## Views

### Category Show Page

The category show page (`resources/views/storefront/categories.show.blade.php`) includes:

- Breadcrumb navigation
- Category header with image and description
- Sub-categories grid
- Product filters (price, brand, sort)
- Products grid with pagination
- SEO meta tags and structured data

### Category Tree Component

```blade
@include('storefront.components.category-tree', ['maxDepth' => 3])
```

## Caching

The category system uses caching for performance:

- Category trees: `categories.tree.{rootId}.{depth}`
- Root categories: `categories.roots`
- Flat lists: `categories.flat.{rootId}`
- Navigation: `categories.navigation.{maxDepth}`
- Breadcrumbs: `category.{id}.breadcrumb`
- Full paths: `category.{id}.full_path`
- Slug lookups: `category.slug.{slug}`

### Clearing Cache

```php
use App\Repositories\CategoryRepository;

$repository = new CategoryRepository();
$repository->clearCache();
```

## Product Count Management

Product counts are automatically cached and updated:

```php
$category = Category::find(1);

// Update product count (called automatically on save)
$category->updateProductCount();

// Count includes products from this category only
$count = $category->product_count;
```

## Files

### Models
- `app/Models/Category.php` - Category model with nested set

### Repositories
- `app/Repositories/CategoryRepository.php` - Category queries

### Services
- `app/Services/CategoryFilterService.php` - Product filtering by categories

### Helpers
- `app/Lunar/Categories/CategoryHelper.php` - Category helper methods
- `app/Lunar/Categories/CategorySEO.php` - SEO helper methods

### Controllers
- `app/Http/Controllers/CategoryController.php` - API endpoints
- `app/Http/Controllers/Storefront/CategoryController.php` - Storefront controller

### Media
- `app/Lunar/Media/CategoryMediaDefinitions.php` - Category image definitions

### Views
- `resources/views/storefront/categories/show.blade.php` - Category page
- `resources/views/storefront/components/category-tree.blade.php` - Navigation component

### Migrations
- `database/migrations/2025_12_25_091447_create_categories_table.php` - Categories table
- `database/migrations/2025_12_25_091452_create_category_product_table.php` - Product-category pivot

## Best Practices

1. **Use Nested Set Methods**: Always use nested set methods (`appendNode`, `prependNode`) for hierarchy
2. **Cache Categories**: Use repository methods which include caching
3. **Update Product Counts**: Counts are auto-updated, but can be manually refreshed
4. **SEO Optimization**: Always set meta_title and meta_description
5. **Image Optimization**: Use appropriate image conversions for different contexts
6. **Breadcrumbs**: Breadcrumbs are auto-generated, no manual setup needed
7. **URL Structure**: Use full paths for better SEO
8. **Filter Products**: Use CategoryFilterService for consistent filtering

## Example Usage

### Complete Category Setup

```php
use App\Models\Category;
use App\Lunar\Categories\CategoryHelper;
use App\Lunar\Media\MediaHelper;

// 1. Create root category
$electronics = Category::create([
    'name' => ['en' => 'Electronics'],
    'description' => ['en' => 'Electronic products'],
    'meta_title' => 'Electronics Store',
    'meta_description' => 'Shop electronics online',
    'is_active' => true,
    'show_in_navigation' => true,
]);

// 2. Add image
MediaHelper::addImage($electronics, $request->file('image'), 'image');

// 3. Create child categories
$phones = CategoryHelper::create([
    'name' => ['en' => 'Phones'],
    'description' => ['en' => 'Mobile phones'],
], $electronics);

$smartphones = CategoryHelper::create([
    'name' => ['en' => 'Smartphones'],
], $phones);

// 4. Associate products
$product = Product::find(1);
$smartphones->attachProducts([$product->id]);

// 5. Get category URL
$url = CategoryHelper::getUrl($smartphones);
// Returns: /categories/electronics/phones/smartphones
```

### Displaying Categories

```php
// In controller
use App\Lunar\Categories\CategoryHelper;

$category = CategoryHelper::findByPath('electronics/phones/smartphones');
$products = $category->getAllProducts();
$breadcrumb = $category->getBreadcrumb();

return view('storefront.categories.show', [
    'category' => $category,
    'products' => $products,
    'breadcrumb' => $breadcrumb,
]);
```

