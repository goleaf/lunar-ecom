<?php

namespace App\Lunar\Categories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Helper class for working with Categories.
 * 
 * Provides convenience methods for category management, navigation, and product filtering.
 */
class CategoryHelper
{
    /**
     * Get category tree structure.
     * 
     * @param Category|null $root Root category (null = all roots)
     * @param int $depth Maximum depth
     * @return Collection|Category
     */
    public static function getTree(?Category $root = null, int $depth = 10): Collection|Category
    {
        if ($root) {
            return static::buildTree($root, $depth);
        }

        return Category::whereIsRoot()
            ->active()
            ->inNavigation()
            ->ordered()
            ->with(['children' => function ($query) use ($depth) {
                static::loadChildrenRecursive($query, $depth - 1);
            }])
            ->get();
    }

    /**
     * Build tree structure for a category.
     * 
     * @param Category $category
     * @param int $depth
     * @return Category
     */
    protected static function buildTree(Category $category, int $depth): Category
    {
        if ($depth > 0) {
            $category->load(['children' => function ($query) use ($depth) {
                $query->active()->inNavigation()->ordered();
                static::loadChildrenRecursive($query, $depth - 1);
            }]);
        }

        return $category;
    }

    /**
     * Recursively load children.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $depth
     * @return void
     */
    protected static function loadChildrenRecursive($query, int $depth): void
    {
        if ($depth > 0) {
            $query->with(['children' => function ($q) use ($depth) {
                $q->active()->inNavigation()->ordered();
                static::loadChildrenRecursive($q, $depth - 1);
            }]);
        }
    }

    /**
     * Get breadcrumb for category.
     * 
     * @param Category $category
     * @return array
     */
    public static function getBreadcrumb(Category $category): array
    {
        return $category->getBreadcrumb();
    }

    /**
     * Get category URL.
     * 
     * @param Category $category
     * @return string
     */
    public static function getUrl(Category $category): string
    {
        return route('categories.show', $category->getFullPath());
    }

    /**
     * Get category image URL.
     * 
     * @param Category $category
     * @param string|null $conversion
     * @return string|null
     */
    public static function getImageUrl(Category $category, ?string $conversion = null): ?string
    {
        return $category->getImageUrl($conversion);
    }

    /**
     * Get all products in category including descendants.
     * 
     * @param Category $category
     * @return Collection
     */
    public static function getAllProducts(Category $category): Collection
    {
        return $category->getAllProducts();
    }

    /**
     * Get category display data.
     * 
     * @param Category $category
     * @return array
     */
    public static function getDisplayData(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->getName(),
            'slug' => $category->slug,
            'description' => $category->getDescription(),
            'url' => static::getUrl($category),
            'image_url' => static::getImageUrl($category, 'thumb'),
            'product_count' => $category->product_count,
            'children_count' => $category->children()->count(),
            'breadcrumb' => static::getBreadcrumb($category),
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
        ];
    }

    /**
     * Get navigation menu structure.
     * 
     * @param int $maxDepth
     * @return Collection
     */
    public static function getNavigation(int $maxDepth = 3): Collection
    {
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
            ->get()
            ->map(function ($category) {
                return static::getDisplayData($category);
            });
    }

    /**
     * Find category by path.
     * 
     * @param string $path Full path like "parent/child/grandchild"
     * @return Category|null
     */
    public static function findByPath(string $path): ?Category
    {
        $segments = explode('/', trim($path, '/'));
        
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
     * Get category path segments.
     * 
     * @param Category $category
     * @return array
     */
    public static function getPathSegments(Category $category): array
    {
        $ancestors = $category->ancestors()->get();
        $segments = [];

        foreach ($ancestors as $ancestor) {
            $segments[] = $ancestor->slug;
        }

        $segments[] = $category->slug;

        return $segments;
    }

    /**
     * Check if category has products.
     * 
     * @param Category $category
     * @param bool $includeDescendants
     * @return bool
     */
    public static function hasProducts(Category $category, bool $includeDescendants = false): bool
    {
        if ($includeDescendants) {
            return $category->getAllProducts()->count() > 0;
        }

        return $category->products()->count() > 0;
    }

    /**
     * Get sibling categories.
     * 
     * @param Category $category
     * @return Collection
     */
    public static function getSiblings(Category $category): Collection
    {
        return $category->getSiblings();
    }

    /**
     * Get parent category.
     * 
     * @param Category $category
     * @return Category|null
     */
    public static function getParent(Category $category): ?Category
    {
        return $category->getParent();
    }

    /**
     * Get child categories.
     * 
     * @param Category $category
     * @return Collection
     */
    public static function getChildren(Category $category): Collection
    {
        return $category->getChildren();
    }

    /**
     * Create category with parent.
     * 
     * @param array $data
     * @param Category|null $parent
     * @return Category
     */
    public static function create(array $data, ?Category $parent = null): Category
    {
        $category = Category::create($data);

        if ($parent) {
            $parent->appendNode($category);
        }

        return $category->fresh();
    }

    /**
     * Move category to new parent.
     * 
     * @param Category $category
     * @param Category|null $newParent
     * @return Category
     */
    public static function move(Category $category, ?Category $newParent = null): Category
    {
        if ($newParent) {
            $newParent->appendNode($category);
        } else {
            $category->makeRoot();
        }

        return $category->fresh();
    }
}

