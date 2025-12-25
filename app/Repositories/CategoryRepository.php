<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Repository for category queries and operations.
 */
class CategoryRepository
{
    /**
     * Find category by slug.
     *
     * @param  string  $slug
     * @return Category|null
     */
    public function findBySlug(string $slug): ?Category
    {
        return Cache::remember("category.slug.{$slug}", 3600, function () use ($slug) {
            return Category::where('slug', $slug)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Find category by full path slug.
     *
     * @param  string  $path  Full path like "parent/child/grandchild"
     * @return Category|null
     */
    public function findByPath(string $path): ?Category
    {
        $segments = array_filter(explode('/', trim($path, '/')));
        
        if (empty($segments)) {
            return null;
        }

        // Start from root and traverse down
        $current = Category::whereIsRoot()
            ->where('slug', $segments[0])
            ->active()
            ->first();

        if (!$current) {
            return null;
        }

        // Traverse through path segments
        for ($i = 1; $i < count($segments); $i++) {
            $current = $current->children()
                ->where('slug', $segments[$i])
                ->active()
                ->first();

            if (!$current) {
                return null;
            }
        }

        return $current;
    }

    /**
     * Get root categories (categories without parent).
     *
     * @return Collection
     */
    public function getRootCategories(): Collection
    {
        return Cache::remember('categories.roots', 3600, function () {
            return Category::whereIsRoot()
                ->active()
                ->ordered()
                ->get();
        });
    }

    /**
     * Get category tree (nested structure).
     *
     * @param  Category|null  $root  Root category to build tree from (null = all roots)
     * @param  int  $depth  Maximum depth to load
     * @return Collection|Category
     */
    public function getCategoryTree(?Category $root = null, int $depth = 10)
    {
        $cacheKey = $root 
            ? "category.tree.{$root->id}.{$depth}"
            : "category.tree.all.{$depth}";

        return Cache::remember($cacheKey, 3600, function () use ($root, $depth) {
            if ($root) {
                return $this->buildTree($root, $depth);
            }

            return Category::whereIsRoot()
                ->active()
                ->ordered()
                ->with(['children' => function ($query) use ($depth) {
                    $this->loadChildrenRecursive($query, $depth - 1);
                }])
                ->get();
        });
    }

    /**
     * Build tree structure for a category.
     *
     * @param  Category  $category
     * @param  int  $depth
     * @return Category
     */
    protected function buildTree(Category $category, int $depth): Category
    {
        if ($depth > 0) {
            $category->load(['children' => function ($query) use ($depth) {
                $query->active()->ordered();
                $this->loadChildrenRecursive($query, $depth - 1);
            }]);
        }

        return $category;
    }

    /**
     * Recursively load children.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $depth
     * @return void
     */
    protected function loadChildrenRecursive($query, int $depth): void
    {
        if ($depth > 0) {
            $query->with(['children' => function ($q) use ($depth) {
                $q->active()->ordered();
                $this->loadChildrenRecursive($q, $depth - 1);
            }]);
        }
    }

    /**
     * Get flat list of categories with indentation.
     *
     * @param  Category|null  $root
     * @return Collection
     */
    public function getFlatList(?Category $root = null): Collection
    {
        $cacheKey = $root 
            ? "categories.flat.{$root->id}"
            : 'categories.flat.all';

        return Cache::remember($cacheKey, 3600, function () use ($root) {
            $query = Category::query()->active()->ordered();
            
            if ($root) {
                $query->whereDescendantOf($root);
            }

            return $query->get()->map(function ($category) {
                $depth = $category->depth;
                $indent = str_repeat('— ', $depth);
                
                return [
                    'id' => $category->id,
                    'name' => $indent . $category->getName(),
                    'slug' => $category->slug,
                    'depth' => $depth,
                    'product_count' => $category->product_count,
                    'is_active' => $category->is_active,
                    'category' => $category,
                ];
            });
        });
    }

    /**
     * Get categories for navigation menu.
     *
     * @param  int  $maxDepth
     * @return Collection
     */
    public function getNavigationCategories(int $maxDepth = 3): Collection
    {
        return Cache::remember("categories.navigation.{$maxDepth}", 3600, function () use ($maxDepth) {
            return Category::whereIsRoot()
                ->active()
                ->inNavigation()
                ->ordered()
                ->with(['children' => function ($query) use ($maxDepth) {
                    $query->active()
                          ->inNavigation()
                          ->ordered()
                          ->where('depth', '<=', $maxDepth);
                }])
                ->get();
        });
    }

    /**
     * Search categories by name.
     *
     * @param  string  $term
     * @param  string|null  $locale
     * @return Collection
     */
    public function search(string $term, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();
        
        return Category::whereJsonContains("name->{$locale}", $term)
            ->orWhere('slug', 'like', "%{$term}%")
            ->orWhere('meta_title', 'like', "%{$term}%")
            ->active()
            ->get();
    }

    /**
     * Get categories with product count above threshold.
     *
     * @param  int  $minProducts
     * @return Collection
     */
    public function getCategoriesWithMinProducts(int $minProducts = 1): Collection
    {
        return Category::where('product_count', '>=', $minProducts)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Clear all category caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('categories.roots');
        Cache::forget('categories.flat.all');
        Cache::forget('categories.navigation.3');
        
        // Clear individual category caches
        Category::all()->each(function ($category) {
            Cache::forget("category.{$category->id}.breadcrumb");
            Cache::forget("category.{$category->id}.full_path");
            Cache::forget("category.slug.{$category->slug}");
        });
    }
}

