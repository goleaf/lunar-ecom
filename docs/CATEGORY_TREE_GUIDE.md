# 🌳 Category Tree with Lunar: Complete Guide

Lunar uses a **nested set model** for categories, making it efficient to handle tree-like structures. This guide covers everything you need to work with categories in your Lunar e-commerce store.

## 📚 Table of Contents

1. [Understanding Nested Set Model](#understanding-nested-set-model)
2. [Creating Categories & Subcategories](#creating-categories--subcategories)
3. [Querying Categories Efficiently](#querying-categories-efficiently)
4. [Assigning Products to Categories](#assigning-products-to-categories)
5. [Category-Specific Promotions](#category-specific-promotions)
6. [Building Category Tree UI](#building-category-tree-ui)
7. [Querying Products by Category](#querying-products-by-category)
8. [Moving & Reordering Categories](#moving--reordering-categories)
9. [Best Practices](#best-practices)

---

## Understanding Nested Set Model

The nested set model stores hierarchical data efficiently using `_lft` (left) and `_rgt` (right) values. This allows:

- ✅ **Fast queries**: Get entire subtrees with a single query
- ✅ **Efficient ancestors/descendants**: No recursive queries needed
- ✅ **Easy reordering**: Move categories without complex updates

### How It Works

```
Electronics (_lft: 1, _rgt: 10)
├── Phones (_lft: 2, _rgt: 7)
│   ├── Smartphones (_lft: 3, _rgt: 4)
│   └── Feature Phones (_lft: 5, _rgt: 6)
└── Tablets (_lft: 8, _rgt: 9)
```

The `kalnoy/nestedset` package provides all the methods you need!

---

## Creating Categories & Subcategories

### Method 1: Using Category Model Directly

```php
use App\Models\Category;

// Create a root category
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
    'display_order' => 1,
]);

// Create a child category using appendNode
$phones = Category::create([
    'name' => ['en' => 'Phones', 'fr' => 'Téléphones'],
    'description' => ['en' => 'Mobile phones and smartphones'],
]);

$electronics->appendNode($phones); // Add as child

// Create nested subcategory
$smartphones = Category::create([
    'name' => ['en' => 'Smartphones', 'fr' => 'Smartphones'],
]);
$phones->appendNode($smartphones);
```

### Method 2: Using CategoryHelper (Recommended)

```php
use App\Lunar\Categories\CategoryHelper;

// Create root category
$electronics = CategoryHelper::create([
    'name' => ['en' => 'Electronics'],
    'description' => ['en' => 'Electronic products'],
    'is_active' => true,
    'show_in_navigation' => true,
]);

// Create child category
$phones = CategoryHelper::create([
    'name' => ['en' => 'Phones'],
    'description' => ['en' => 'Mobile phones'],
], $electronics); // Pass parent as second parameter

// Create nested subcategory
$smartphones = CategoryHelper::create([
    'name' => ['en' => 'Smartphones'],
], $phones);
```

### Method 3: Using Nested Set Methods

```php
use App\Models\Category;

$electronics = Category::create(['name' => ['en' => 'Electronics']]);

// Add as first child
$phones = Category::create(['name' => ['en' => 'Phones']]);
$electronics->prependNode($phones);

// Add as last child (default)
$tablets = Category::create(['name' => ['en' => 'Tablets']]);
$electronics->appendNode($tablets);

// Insert after a sibling
$laptops = Category::create(['name' => ['en' => 'Laptops']]);
$phones->insertAfter($laptops);
```

---

## Querying Categories Efficiently

### Get Root Categories

```php
use App\Models\Category;

// Get all root categories (no parent)
$roots = Category::whereIsRoot()
    ->active()
    ->inNavigation()
    ->ordered()
    ->get();
```

### Get Category Tree

```php
use App\Lunar\Categories\CategoryHelper;
use App\Repositories\CategoryRepository;

// Method 1: Using CategoryHelper
$tree = CategoryHelper::getTree(); // All roots with children
$tree = CategoryHelper::getTree($electronics, 5); // From specific root, max depth 5

// Method 2: Using CategoryRepository (with caching)
$repository = new CategoryRepository();
$tree = $repository->getCategoryTree(); // All roots
$tree = $repository->getCategoryTree($electronics, 3); // Specific root, depth 3
```

### Get Children & Descendants

```php
$category = Category::find(1);

// Get direct children only
$children = $category->getChildren();
// or
$children = $category->children()->defaultOrder()->get();

// Get all descendants (children, grandchildren, etc.)
$descendants = $category->getDescendants();
// or
$descendants = $category->descendants()->get();

// Get ancestors (all parents up to root)
$ancestors = $category->getAncestors();
// or
$ancestors = $category->ancestors()->get();

// Get siblings (categories with same parent)
$siblings = $category->getSiblings();
// or
$siblings = $category->siblings()->defaultOrder()->get();
```

### Find Category by Path

```php
use App\Lunar\Categories\CategoryHelper;
use App\Repositories\CategoryRepository;

// Find by full path slug
$category = CategoryHelper::findByPath('electronics/phones/smartphones');

// Or using repository
$repository = new CategoryRepository();
$category = $repository->findByPath('electronics/phones/smartphones');
```

### Get Flat List (for dropdowns)

```php
use App\Repositories\CategoryRepository;

$repository = new CategoryRepository();
$flatList = $repository->getFlatList(); // All categories with indentation
$flatList = $repository->getFlatList($electronics); // From specific root

// Result format:
// [
//     ['id' => 1, 'name' => 'Electronics', 'depth' => 0],
//     ['id' => 2, 'name' => '— Phones', 'depth' => 1],
//     ['id' => 3, 'name' => '— — Smartphones', 'depth' => 2],
// ]
```

---

## Assigning Products to Categories

### Assign Single Product

```php
use App\Models\Category;
use App\Models\Product;

$category = Category::find(1);
$product = Product::find(1);

// Method 1: Using relationship
$category->products()->attach($product->id, ['position' => 1]);

// Method 2: Using helper method (updates product count automatically)
$category->attachProducts([$product->id], ['position' => 1]);
```

### Assign Multiple Products

```php
$category = Category::find(1);

// Attach multiple products
$category->attachProducts([1, 2, 3, 4], ['position' => 0]);

// Or using sync (replaces all existing)
$category->products()->sync([
    1 => ['position' => 1],
    2 => ['position' => 2],
    3 => ['position' => 3],
]);
```

### Assign Product to Multiple Categories

```php
$product = Product::find(1);
$categories = [1, 2, 3]; // Category IDs

// Attach to multiple categories
$product->categories()->syncWithoutDetaching($categories);

// Or sync (replaces all existing category associations)
$product->categories()->sync($categories);
```

### Remove Products from Category

```php
$category = Category::find(1);

// Detach single product
$category->detachProducts([$product->id]);

// Detach multiple products
$category->detachProducts([1, 2, 3]);
```

### Get Products in Category

```php
$category = Category::find(1);

// Get products directly in this category
$products = $category->products;

// Get all products including from descendant categories
$allProducts = $category->getAllProducts();

// Get products with pagination
$products = $category->products()->paginate(20);
```

---

## Category-Specific Promotions

### Check if Product is in Category

```php
use App\Lunar\Categories\CategoryHelper;

$category = Category::find(1);
$product = Product::find(1);

// Check if product is directly in category
$isInCategory = $category->products()->where('products.id', $product->id)->exists();

// Check if product is in category or any descendant
$categoryIds = $category->descendants()->pluck('id')->push($category->id);
$isInCategoryTree = $product->categories()->whereIn('categories.id', $categoryIds)->exists();
```

### Apply Promotion to Category

```php
use App\Models\Category;
use App\Models\Product;

// Example: 20% off all products in Electronics category
$electronics = Category::find(1);

// Get all products in category tree
$products = $electronics->getAllProducts();

// Apply discount
foreach ($products as $product) {
    // Your promotion logic here
    $product->applyDiscount(20); // Example method
}
```

### Filter Products by Category for Promotion

```php
use App\Services\CategoryFilterService;

$filterService = new CategoryFilterService();
$query = Product::query();

// Get products in Electronics category (including subcategories)
$electronics = Category::find(1);
$query = $filterService->filterByCategory($query, $electronics, true);

// Apply promotion to filtered products
$products = $query->get();
```

---

## Building Category Tree UI

### Backend: Prepare Tree Data

```php
// In your controller
use App\Lunar\Categories\CategoryHelper;

public function index()
{
    // Get category tree for navigation
    $categoryTree = CategoryHelper::getTree(null, 3); // Max depth 3
    
    // Or get navigation menu
    $navigation = CategoryHelper::getNavigation(3);
    
    return view('frontend.categories.index', [
        'categoryTree' => $categoryTree,
        'navigation' => $navigation,
    ]);
}
```

### Frontend: Blade Component

```blade
{{-- resources/views/frontend/components/category-tree.blade.php --}}
<ul class="category-tree">
    @foreach($categories as $category)
        <li class="category-item">
            <a href="{{ route('categories.show', $category->getFullPath()) }}">
                {{ $category->getName() }}
                @if($category->product_count > 0)
                    <span class="count">({{ $category->product_count }})</span>
                @endif
            </a>
            
            @if($category->children->count() > 0)
                <ul class="subcategories">
                    @include('frontend.components.category-tree', [
                        'categories' => $category->children
                    ])
                </ul>
            @endif
        </li>
    @endforeach
</ul>
```

### Frontend: Vue/React Component (JSON API)

```php
// In your API controller
public function tree()
{
    $tree = CategoryHelper::getTree(null, 5);
    
    return response()->json([
        'categories' => $tree->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->getName(),
                'slug' => $category->slug,
                'url' => CategoryHelper::getUrl($category),
                'product_count' => $category->product_count,
                'image_url' => CategoryHelper::getImageUrl($category, 'thumb'),
                'children' => $this->formatChildren($category->children),
            ];
        }),
    ]);
}

protected function formatChildren($children)
{
    return $children->map(function ($child) {
        return [
            'id' => $child->id,
            'name' => $child->getName(),
            'slug' => $child->slug,
            'url' => CategoryHelper::getUrl($child),
            'product_count' => $child->product_count,
            'children' => $this->formatChildren($child->children),
        ];
    });
}
```

### Frontend: JavaScript Example

```javascript
// Fetch category tree
fetch('/api/categories/tree')
    .then(response => response.json())
    .then(data => {
        renderCategoryTree(data.categories);
    });

function renderCategoryTree(categories, parentElement = null) {
    const ul = document.createElement('ul');
    ul.className = 'category-tree';
    
    categories.forEach(category => {
        const li = document.createElement('li');
        li.innerHTML = `
            <a href="${category.url}">
                ${category.name}
                ${category.product_count > 0 ? `(${category.product_count})` : ''}
            </a>
        `;
        
        if (category.children && category.children.length > 0) {
            renderCategoryTree(category.children, li);
        }
        
        ul.appendChild(li);
    });
    
    if (parentElement) {
        parentElement.appendChild(ul);
    } else {
        document.getElementById('category-nav').appendChild(ul);
    }
}
```

---

## Querying Products by Category

### Basic Category Filtering

```php
use App\Services\CategoryFilterService;

$filterService = new CategoryFilterService();
$query = Product::query();

// Filter by single category (includes descendants)
$electronics = Category::find(1);
$query = $filterService->filterByCategory($query, $electronics, true);

// Filter by single category (excludes descendants)
$query = $filterService->filterByCategory($query, $electronics, false);

// Get results
$products = $query->paginate(20);
```

### Multiple Categories (OR Logic)

```php
// Products in ANY of these categories
$categoryIds = [1, 2, 3];
$query = $filterService->filterByCategoriesOr($query, $categoryIds);
$products = $query->get();
```

### Multiple Categories (AND Logic)

```php
// Products must be in ALL of these categories
$categoryIds = [1, 2, 3];
$query = $filterService->filterByCategoriesAnd($query, $categoryIds);
$products = $query->get();
```

### Exclude Categories

```php
// Exclude products from specific categories
$excludeIds = [4, 5];
$query = $filterService->excludeCategories($query, $excludeIds);
$products = $query->get();
```

### Complex Filtering

```php
// Mixed AND/OR logic
$filters = [
    [
        'logic' => 'or',
        'categories' => [1, 2, 3], // Electronics OR Phones OR Tablets
    ],
    [
        'logic' => 'and',
        'categories' => [4], // AND must be in Smartphones
    ],
];

$query = $filterService->filterByMixedLogic($query, $filters);
$products = $query->get();
```

### In Controller

```php
use App\Http\Controllers\Controller;
use App\Services\CategoryFilterService;
use App\Models\Product;
use App\Lunar\Categories\CategoryHelper;

class CategoryController extends Controller
{
    public function show($path)
    {
        $category = CategoryHelper::findByPath($path);
        
        if (!$category) {
            abort(404);
        }
        
        $filterService = new CategoryFilterService();
        $query = Product::query();
        
        // Filter by category (including subcategories)
        $query = $filterService->filterByCategory($query, $category, true);
        
        // Apply additional filters (price, brand, etc.)
        if (request()->has('min_price')) {
            $query->where('price', '>=', request('min_price'));
        }
        
        if (request()->has('max_price')) {
            $query->where('price', '<=', request('max_price'));
        }
        
        // Sort
        $sort = request('sort', 'default');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        $products = $query->paginate(20);
        
        return view('frontend.categories.show', [
            'category' => $category,
            'products' => $products,
            'breadcrumb' => $category->getBreadcrumb(),
        ]);
    }
}
```

---

## Moving & Reordering Categories

### Move Category to New Parent

```php
use App\Lunar\Categories\CategoryHelper;

$category = Category::find(3);
$newParent = Category::find(1);

// Move category
CategoryHelper::move($category, $newParent);

// Or make it a root category
CategoryHelper::move($category, null);
```

### Using Nested Set Methods

```php
$category = Category::find(3);
$newParent = Category::find(1);

// Move to new parent
$newParent->appendNode($category);

// Or make root
$category->makeRoot();

// Move before another category
$category->insertBefore($sibling);

// Move after another category
$category->insertAfter($sibling);
```

### Reorder Siblings

```php
$category = Category::find(3);

// Move to first position
$category->moveToStart();

// Move to last position
$category->moveToEnd();

// Move to specific position
$category->moveTo(2); // Move to position 2 (0-indexed)
```

### Update Display Order

```php
$category = Category::find(3);
$category->display_order = 5;
$category->save();

// Reorder all siblings
$siblings = $category->siblings()->orderBy('display_order')->get();
foreach ($siblings as $index => $sibling) {
    $sibling->display_order = $index + 1;
    $sibling->save();
}
```

---

## Best Practices

### 1. Use Caching

```php
// Always use repository methods which include caching
$repository = new CategoryRepository();
$tree = $repository->getCategoryTree(); // Cached for 1 hour

// Clear cache when needed
$repository->clearCache();
```

### 2. Use Scopes

```php
// Always filter by active and navigation
$categories = Category::whereIsRoot()
    ->active()           // Only active categories
    ->inNavigation()     // Only show in navigation
    ->ordered()          // Order by display_order
    ->get();
```

### 3. Efficient Queries

```php
// ✅ Good: Eager load relationships
$categories = Category::with('children')->whereIsRoot()->get();

// ❌ Bad: N+1 queries
$categories = Category::whereIsRoot()->get();
foreach ($categories as $category) {
    $children = $category->children; // Query for each category
}
```

### 4. Update Product Counts

```php
// Product counts are auto-updated, but you can manually refresh
$category->updateProductCount();

// Or update all counts
php artisan category:update-product-counts
```

### 5. Use Helper Methods

```php
// ✅ Good: Use helper methods
$category = CategoryHelper::findByPath('electronics/phones');
$url = CategoryHelper::getUrl($category);

// ❌ Bad: Manual path building
$segments = explode('/', $path);
// ... complex logic
```

### 6. Handle Translations

```php
// Always use getName() and getDescription() for translations
$name = $category->getName(); // Current locale
$nameFr = $category->getName('fr'); // French
```

### 7. SEO-Friendly URLs

```php
// Use full path for better SEO
$url = route('categories.show', $category->getFullPath());
// Result: /categories/electronics/phones/smartphones
```

### 8. Validate Category Operations

```php
// Check if category exists and is active
$category = Category::where('slug', $slug)
    ->active()
    ->firstOrFail();

// Check if category has products before deletion
if ($category->products()->count() > 0) {
    throw new \Exception('Cannot delete category with products');
}
```

---

## Quick Reference

### Common Operations

```php
// Create category
$category = CategoryHelper::create(['name' => ['en' => 'Electronics']], $parent);

// Get tree
$tree = CategoryHelper::getTree();

// Find by path
$category = CategoryHelper::findByPath('electronics/phones');

// Get products
$products = $category->getAllProducts();

// Filter products
$filterService = new CategoryFilterService();
$query = $filterService->filterByCategory(Product::query(), $category, true);

// Move category
CategoryHelper::move($category, $newParent);

// Get breadcrumb
$breadcrumb = $category->getBreadcrumb();
```

### Nested Set Methods

```php
// Tree operations
$category->appendNode($child);      // Add as last child
$category->prependNode($child);      // Add as first child
$category->insertAfter($sibling);    // Insert after sibling
$category->insertBefore($sibling);    // Insert before sibling
$category->makeRoot();               // Make root category

// Queries
Category::whereIsRoot();              // Root categories
$category->ancestors;                 // All ancestors
$category->descendants;               // All descendants
$category->siblings;                  // Siblings
$category->children;                  // Direct children
```

---

## 🎉 You're All Set!

You now have everything you need to work with Lunar's nested set category system. The nested set model makes category management efficient and flexible, allowing you to:

- ✅ Create unlimited category depth
- ✅ Query categories efficiently
- ✅ Assign products to multiple categories
- ✅ Build beautiful category trees
- ✅ Handle category-specific promotions
- ✅ Move and reorder categories easily

Happy coding! 🚀


