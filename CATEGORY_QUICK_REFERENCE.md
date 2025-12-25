# 🚀 Category Tree Quick Reference

Quick cheat sheet for working with Lunar's nested set category system.

## 📦 Import Statements

```php
use App\Models\Category;
use App\Models\Product;
use App\Lunar\Categories\CategoryHelper;
use App\Repositories\CategoryRepository;
use App\Services\CategoryFilterService;
```

## 🌳 Creating Categories

```php
// Root category
$category = CategoryHelper::create(['name' => ['en' => 'Electronics']]);

// Child category
$child = CategoryHelper::create(['name' => ['en' => 'Phones']], $category);

// Using nested set methods
$category->appendNode($child);  // Add as last child
$category->prependNode($child); // Add as first child
```

## 🔍 Finding Categories

```php
// By ID
$category = Category::find(1);

// By slug
$category = Category::where('slug', 'electronics')->first();

// By path
$category = CategoryHelper::findByPath('electronics/phones/smartphones');

// Root categories
$roots = Category::whereIsRoot()->active()->get();
```

## 📊 Getting Category Tree

```php
// Full tree
$tree = CategoryHelper::getTree();

// From specific root
$tree = CategoryHelper::getTree($category, 5); // Max depth 5

// Using repository (cached)
$repository = new CategoryRepository();
$tree = $repository->getCategoryTree();
```

## 👨‍👩‍👧‍👦 Relationships

```php
$category = Category::find(1);

// Get children
$children = $category->getChildren();
$children = $category->children;

// Get descendants (all children recursively)
$descendants = $category->getDescendants();
$descendants = $category->descendants;

// Get ancestors (all parents)
$ancestors = $category->getAncestors();
$ancestors = $category->ancestors;

// Get siblings
$siblings = $category->getSiblings();
$siblings = $category->siblings;

// Get parent
$parent = $category->getParent();
$parent = $category->parent;
```

## 🛍️ Products & Categories

```php
$category = Category::find(1);
$product = Product::find(1);

// Attach product
$category->attachProducts([$product->id]);

// Attach multiple
$category->attachProducts([1, 2, 3], ['position' => 1]);

// Detach product
$category->detachProducts([$product->id]);

// Get products
$products = $category->products;
$allProducts = $category->getAllProducts(); // Includes subcategories

// Product to multiple categories
$product->categories()->syncWithoutDetaching([1, 2, 3]);
```

## 🔎 Filtering Products by Category

```php
$filterService = new CategoryFilterService();

// Single category
$query = $filterService->filterByCategory(Product::query(), $category, true);

// Multiple categories (OR)
$query = $filterService->filterByCategoriesOr(Product::query(), [1, 2, 3]);

// Multiple categories (AND)
$query = $filterService->filterByCategoriesAnd(Product::query(), [1, 2, 3]);

// Exclude categories
$query = $filterService->excludeCategories(Product::query(), [4, 5]);
```

## 🔄 Moving Categories

```php
$category = Category::find(3);
$newParent = Category::find(1);

// Move to new parent
CategoryHelper::move($category, $newParent);

// Make root
CategoryHelper::move($category, null);

// Using nested set
$newParent->appendNode($category);
$category->makeRoot();
$category->insertAfter($sibling);
```

## 🧭 Navigation & URLs

```php
$category = Category::find(1);

// Get URL
$url = CategoryHelper::getUrl($category);
$url = route('categories.show', $category->getFullPath());

// Get breadcrumb
$breadcrumb = $category->getBreadcrumb();

// Get full path
$path = $category->getFullPath(); // "electronics/phones/smartphones"
```

## 🎨 Display Data

```php
$category = Category::find(1);

// Get name (translatable)
$name = $category->getName();
$nameFr = $category->getName('fr');

// Get description
$description = $category->getDescription();

// Get image
$image = CategoryHelper::getImageUrl($category, 'thumb');

// Get display data
$data = CategoryHelper::getDisplayData($category);
```

## 📋 Scopes

```php
// Active categories
Category::active()->get();

// In navigation
Category::inNavigation()->get();

// Ordered
Category::ordered()->get();

// Combined
Category::whereIsRoot()
    ->active()
    ->inNavigation()
    ->ordered()
    ->get();
```

## 🎯 Common Patterns

### Category Page Controller

```php
public function show($path)
{
    $category = CategoryHelper::findByPath($path);
    $filterService = new CategoryFilterService();
    
    $query = $filterService->filterByCategory(
        Product::query(),
        $category,
        true
    );
    
    $products = $query->paginate(20);
    
    return view('categories.show', [
        'category' => $category,
        'products' => $products,
        'breadcrumb' => $category->getBreadcrumb(),
    ]);
}
```

### Navigation Menu

```php
$navigation = CategoryHelper::getNavigation(3); // Max depth 3
```

### Category Dropdown

```php
$repository = new CategoryRepository();
$flatList = $repository->getFlatList();
// Returns: [['id' => 1, 'name' => 'Electronics', 'depth' => 0], ...]
```

## 💾 Caching

```php
// Clear cache
$repository = new CategoryRepository();
$repository->clearCache();

// Cache keys
'categories.roots'
'categories.tree.{rootId}.{depth}'
'categories.flat.{rootId}'
'categories.navigation.{maxDepth}'
'category.{id}.breadcrumb'
'category.{id}.full_path'
```

## ⚡ Performance Tips

1. **Use eager loading**
   ```php
   Category::with('children')->whereIsRoot()->get();
   ```

2. **Use repository methods** (they're cached)
   ```php
   $repository = new CategoryRepository();
   $tree = $repository->getCategoryTree();
   ```

3. **Limit depth** when possible
   ```php
   CategoryHelper::getTree(null, 3); // Max depth 3
   ```

4. **Use scopes** for filtering
   ```php
   Category::active()->inNavigation()->get();
   ```

## 🐛 Troubleshooting

### Category not found by path
```php
// Check if category exists and is active
$category = Category::where('slug', $slug)
    ->active()
    ->first();
```

### Product count not updating
```php
// Manually update
$category->updateProductCount();
```

### Cache issues
```php
// Clear all category caches
$repository = new CategoryRepository();
$repository->clearCache();
```

---

**📚 Full Documentation**: See `CATEGORY_TREE_GUIDE.md` for complete guide  
**💡 Examples**: See `examples/category-examples.php` for real-world scenarios

