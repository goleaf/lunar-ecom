<?php

/**
 * Practical Examples: Working with Lunar Category Tree
 * 
 * This file contains real-world examples of using Lunar's nested set category system.
 * Copy and adapt these examples for your use case.
 */

use App\Models\Category;
use App\Models\Product;
use App\Lunar\Categories\CategoryHelper;
use App\Repositories\CategoryRepository;
use App\Services\CategoryFilterService;

// ============================================================================
// EXAMPLE 1: Setting Up a Complete Category Structure
// ============================================================================

function setupElectronicsCategoryTree()
{
    // Create root category
    $electronics = CategoryHelper::create([
        'name' => [
            'en' => 'Electronics',
            'fr' => 'Électronique',
            'de' => 'Elektronik',
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

    // Create first-level categories
    $phones = CategoryHelper::create([
        'name' => ['en' => 'Phones', 'fr' => 'Téléphones'],
        'description' => ['en' => 'Mobile phones and smartphones'],
        'is_active' => true,
        'show_in_navigation' => true,
        'display_order' => 1,
    ], $electronics);

    $tablets = CategoryHelper::create([
        'name' => ['en' => 'Tablets', 'fr' => 'Tablettes'],
        'description' => ['en' => 'Tablet computers'],
        'is_active' => true,
        'show_in_navigation' => true,
        'display_order' => 2,
    ], $electronics);

    $laptops = CategoryHelper::create([
        'name' => ['en' => 'Laptops', 'fr' => 'Ordinateurs portables'],
        'description' => ['en' => 'Laptop computers'],
        'is_active' => true,
        'show_in_navigation' => true,
        'display_order' => 3,
    ], $electronics);

    // Create second-level categories (subcategories)
    $smartphones = CategoryHelper::create([
        'name' => ['en' => 'Smartphones', 'fr' => 'Smartphones'],
        'description' => ['en' => 'Advanced mobile phones'],
        'is_active' => true,
        'show_in_navigation' => true,
    ], $phones);

    $featurePhones = CategoryHelper::create([
        'name' => ['en' => 'Feature Phones', 'fr' => 'Téléphones basiques'],
        'description' => ['en' => 'Basic mobile phones'],
        'is_active' => true,
        'show_in_navigation' => true,
    ], $phones);

    // Create third-level categories (sub-subcategories)
    $androidPhones = CategoryHelper::create([
        'name' => ['en' => 'Android Phones', 'fr' => 'Téléphones Android'],
        'is_active' => true,
        'show_in_navigation' => true,
    ], $smartphones);

    $iphones = CategoryHelper::create([
        'name' => ['en' => 'iPhones', 'fr' => 'iPhones'],
        'is_active' => true,
        'show_in_navigation' => true,
    ], $smartphones);

    return $electronics;
}

// ============================================================================
// EXAMPLE 2: Assigning Products to Multiple Categories
// ============================================================================

function assignProductToMultipleCategories()
{
    $product = Product::find(1); // Your product
    
    // Find categories
    $smartphones = CategoryHelper::findByPath('electronics/phones/smartphones');
    $androidPhones = CategoryHelper::findByPath('electronics/phones/smartphones/android-phones');
    $featured = CategoryHelper::findByPath('featured'); // Example featured category
    
    // Assign product to multiple categories
    $product->categories()->syncWithoutDetaching([
        $smartphones->id,
        $androidPhones->id,
        $featured->id,
    ]);
    
    // Or with pivot data (position)
    $product->categories()->syncWithoutDetaching([
        $smartphones->id => ['position' => 1],
        $androidPhones->id => ['position' => 2],
        $featured->id => ['position' => 0], // Featured first
    ]);
}

// ============================================================================
// EXAMPLE 3: Building a Category Navigation Menu
// ============================================================================

function buildCategoryNavigation()
{
    $repository = new CategoryRepository();
    
    // Get navigation categories (max depth 3)
    $navigation = $repository->getNavigationCategories(3);
    
    // Format for frontend
    $menu = $navigation->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->getName(),
            'url' => CategoryHelper::getUrl($category),
            'image' => CategoryHelper::getImageUrl($category, 'thumb'),
            'product_count' => $category->product_count,
            'children' => $category->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->getName(),
                    'url' => CategoryHelper::getUrl($child),
                    'product_count' => $child->product_count,
                    'children' => $child->children->map(function ($grandchild) {
                        return [
                            'id' => $grandchild->id,
                            'name' => $grandchild->getName(),
                            'url' => CategoryHelper::getUrl($grandchild),
                            'product_count' => $grandchild->product_count,
                        ];
                    }),
                ];
            }),
        ];
    });
    
    return $menu;
}

// ============================================================================
// EXAMPLE 4: Filtering Products by Category with Additional Filters
// ============================================================================

function filterProductsByCategory()
{
    $category = CategoryHelper::findByPath('electronics/phones/smartphones');
    $filterService = new CategoryFilterService();
    
    // Start with category filter
    $query = $filterService->filterByCategory(
        Product::query(),
        $category,
        true // Include subcategories
    );
    
    // Add price filter
    $minPrice = request('min_price', 0);
    $maxPrice = request('max_price', 10000);
    $query->whereBetween('price', [$minPrice, $maxPrice]);
    
    // Add brand filter
    if (request()->has('brand_id')) {
        $query->where('brand_id', request('brand_id'));
    }
    
    // Add stock filter
    $query->where('stock', '>', 0);
    
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
        case 'popular':
            $query->orderBy('views', 'desc');
            break;
        default:
            $query->orderBy('display_order')->orderBy('name');
    }
    
    return $query->paginate(20);
}

// ============================================================================
// EXAMPLE 5: Category-Specific Promotion
// ============================================================================

function applyCategoryPromotion()
{
    // Find category
    $electronics = CategoryHelper::findByPath('electronics');
    
    // Get all products in category tree
    $products = $electronics->getAllProducts();
    
    // Apply 20% discount to all products
    foreach ($products as $product) {
        // Your promotion logic here
        // Example: Update product price
        $originalPrice = $product->price;
        $discountedPrice = $originalPrice * 0.8; // 20% off
        
        // Create promotion record or update product
        // $product->update(['sale_price' => $discountedPrice]);
    }
    
    return $products;
}

// ============================================================================
// EXAMPLE 6: Moving Categories in the Tree
// ============================================================================

function reorganizeCategoryTree()
{
    // Find categories
    $oldCategory = Category::find(5);
    $newParent = Category::find(2);
    
    // Move category to new parent
    CategoryHelper::move($oldCategory, $newParent);
    
    // Or make it a root category
    // CategoryHelper::move($oldCategory, null);
    
    // Or move to specific position
    $oldCategory->insertAfter($newParent->children()->first());
}

// ============================================================================
// EXAMPLE 7: Getting Category Breadcrumbs
// ============================================================================

function getCategoryBreadcrumbs()
{
    $category = CategoryHelper::findByPath('electronics/phones/smartphones/android-phones');
    
    // Get breadcrumb array
    $breadcrumb = $category->getBreadcrumb();
    
    // Format: [
    //     ['id' => 1, 'name' => 'Electronics', 'url' => '/categories/electronics'],
    //     ['id' => 2, 'name' => 'Phones', 'url' => '/categories/electronics/phones'],
    //     ['id' => 3, 'name' => 'Smartphones', 'url' => '/categories/electronics/phones/smartphones'],
    //     ['id' => 4, 'name' => 'Android Phones', 'url' => '/categories/electronics/phones/smartphones/android-phones'],
    // ]
    
    return $breadcrumb;
}

// ============================================================================
// EXAMPLE 8: Finding Related Categories
// ============================================================================

function findRelatedCategories()
{
    $category = Category::find(5);
    
    // Get sibling categories (same parent)
    $siblings = $category->getSiblings();
    
    // Get parent category
    $parent = $category->getParent();
    
    // Get child categories
    $children = $category->getChildren();
    
    // Get categories at same level (all siblings of parent)
    $cousins = $parent ? $parent->getChildren() : collect();
    
    return [
        'current' => $category,
        'parent' => $parent,
        'siblings' => $siblings,
        'children' => $children,
        'cousins' => $cousins,
    ];
}

// ============================================================================
// EXAMPLE 9: Bulk Category Operations
// ============================================================================

function bulkCategoryOperations()
{
    // Get all root categories
    $roots = Category::whereIsRoot()->active()->get();
    
    // Update all root categories
    foreach ($roots as $root) {
        // Update product counts
        $root->updateProductCount();
        
        // Update display order based on product count
        // $root->display_order = $root->product_count;
        // $root->save();
    }
    
    // Get all categories with no products
    $emptyCategories = Category::where('product_count', 0)
        ->whereDoesntHave('children')
        ->get();
    
    // Optionally deactivate empty categories
    foreach ($emptyCategories as $category) {
        $category->is_active = false;
        $category->save();
    }
}

// ============================================================================
// EXAMPLE 10: Category Search with Autocomplete
// ============================================================================

function searchCategories($searchTerm)
{
    $repository = new CategoryRepository();
    
    // Search by name
    $results = $repository->search($searchTerm);
    
    // Format for autocomplete
    $suggestions = $results->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->getName(),
            'path' => $category->getFullPath(),
            'url' => CategoryHelper::getUrl($category),
            'breadcrumb' => implode(' > ', array_column($category->getBreadcrumb(), 'name')),
        ];
    });
    
    return $suggestions;
}

// ============================================================================
// EXAMPLE 11: Category Statistics
// ============================================================================

function getCategoryStatistics()
{
    $repository = new CategoryRepository();
    
    // Get all categories
    $allCategories = Category::all();
    
    // Get root categories
    $rootCategories = Category::whereIsRoot()->count();
    
    // Get categories with products
    $categoriesWithProducts = Category::where('product_count', '>', 0)->count();
    
    // Get average depth
    $avgDepth = Category::avg('depth');
    
    // Get deepest category
    $deepestCategory = Category::orderBy('depth', 'desc')->first();
    
    // Get category with most products
    $mostProducts = Category::orderBy('product_count', 'desc')->first();
    
    return [
        'total_categories' => $allCategories->count(),
        'root_categories' => $rootCategories,
        'categories_with_products' => $categoriesWithProducts,
        'average_depth' => round($avgDepth, 2),
        'deepest_category' => $deepestCategory ? [
            'id' => $deepestCategory->id,
            'name' => $deepestCategory->getName(),
            'depth' => $deepestCategory->depth,
        ] : null,
        'most_products' => $mostProducts ? [
            'id' => $mostProducts->id,
            'name' => $mostProducts->getName(),
            'product_count' => $mostProducts->product_count,
        ] : null,
    ];
}

// ============================================================================
// EXAMPLE 12: Export Category Tree to JSON
// ============================================================================

function exportCategoryTreeToJson()
{
    $tree = CategoryHelper::getTree();
    
    $export = $tree->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
            'is_active' => $category->is_active,
            'show_in_navigation' => $category->show_in_navigation,
            'display_order' => $category->display_order,
            'product_count' => $category->product_count,
            'children' => formatCategoryChildren($category->children),
        ];
    });
    
    return json_encode($export, JSON_PRETTY_PRINT);
}

function formatCategoryChildren($children)
{
    return $children->map(function ($child) {
        return [
            'id' => $child->id,
            'name' => $child->name,
            'slug' => $child->slug,
            'product_count' => $child->product_count,
            'children' => formatCategoryChildren($child->children),
        ];
    });
}

// ============================================================================
// EXAMPLE 13: Import Category Tree from Array
// ============================================================================

function importCategoryTreeFromArray($categories, $parent = null)
{
    foreach ($categories as $categoryData) {
        // Create category
        $category = CategoryHelper::create([
            'name' => $categoryData['name'] ?? ['en' => 'Unnamed'],
            'description' => $categoryData['description'] ?? null,
            'meta_title' => $categoryData['meta_title'] ?? null,
            'meta_description' => $categoryData['meta_description'] ?? null,
            'is_active' => $categoryData['is_active'] ?? true,
            'show_in_navigation' => $categoryData['show_in_navigation'] ?? true,
            'display_order' => $categoryData['display_order'] ?? 0,
        ], $parent);
        
        // Recursively import children
        if (isset($categoryData['children']) && is_array($categoryData['children'])) {
            importCategoryTreeFromArray($categoryData['children'], $category);
        }
    }
}

// Usage:
// $categories = [
//     [
//         'name' => ['en' => 'Electronics'],
//         'children' => [
//             ['name' => ['en' => 'Phones']],
//             ['name' => ['en' => 'Tablets']],
//         ],
//     ],
// ];
// importCategoryTreeFromArray($categories);

// ============================================================================
// EXAMPLE 14: Category-Based Product Recommendations
// ============================================================================

function getCategoryBasedRecommendations($product)
{
    // Get product's categories
    $productCategories = $product->categories;
    
    // Get products from same categories
    $recommendations = Product::whereHas('categories', function ($query) use ($productCategories) {
        $query->whereIn('categories.id', $productCategories->pluck('id'));
    })
    ->where('id', '!=', $product->id)
    ->inStock()
    ->limit(10)
    ->get();
    
    return $recommendations;
}

// ============================================================================
// EXAMPLE 15: Category Path Validation
// ============================================================================

function validateCategoryPath($path)
{
    $segments = explode('/', trim($path, '/'));
    
    if (empty($segments)) {
        return false;
    }
    
    // Start from root
    $current = Category::whereIsRoot()
        ->where('slug', $segments[0])
        ->active()
        ->first();
    
    if (!$current) {
        return false;
    }
    
    // Traverse path
    for ($i = 1; $i < count($segments); $i++) {
        $current = $current->children()
            ->where('slug', $segments[$i])
            ->active()
            ->first();
        
        if (!$current) {
            return false;
        }
    }
    
    return $current;
}

