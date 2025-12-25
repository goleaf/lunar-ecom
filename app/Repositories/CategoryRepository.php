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
     * @param  \Lunar\Models\Channel|null  $channel
     * @param  \Lunar\Models\Language|null  $language
     * @return Category|null
     */
    public function findBySlug(string $slug, $channel = null, $language = null): ?Category
    {
        $cacheKey = "category.slug.{$slug}";
        if ($channel) {
            $cacheKey .= ".channel.{$channel->id}";
        }
        if ($language) {
            $cacheKey .= ".lang.{$language->id}";
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($slug, $channel, $language) {
            $query = Category::where('slug', $slug);
            
            if ($channel) {
                $query->visibleInChannel($channel);
            } else {
                $query->where('is_active', true);
            }
            
            if ($language) {
                $query->visibleInLanguage($language);
            }
            
            return $query->first();
        });
    }

    /**
     * Find category by full path slug.
     *
     * @param  string  $path  Full path like "parent/child/grandchild"
     * @param  \Lunar\Models\Channel|null  $channel
     * @param  \Lunar\Models\Language|null  $language
     * @return Category|null
     */
    public function findByPath(string $path, $channel = null, $language = null): ?Category
    {
        $segments = array_filter(explode('/', trim($path, '/')));
        
        if (empty($segments)) {
            return null;
        }

        // Start from root and traverse down
        $query = Category::whereIsRoot()->where('slug', $segments[0]);
        
        if ($channel) {
            $query->visibleInChannel($channel);
        } else {
            $query->active();
        }
        
        if ($language) {
            $query->visibleInLanguage($language);
        }
        
        $current = $query->first();

        if (!$current) {
            return null;
        }

        // Traverse through path segments
        for ($i = 1; $i < count($segments); $i++) {
            $childQuery = $current->children()->where('slug', $segments[$i]);
            
            if ($channel) {
                $childQuery->visibleInChannel($channel);
            } else {
                $childQuery->active();
            }
            
            if ($language) {
                $childQuery->visibleInLanguage($language);
            }
            
            $current = $childQuery->first();

            if (!$current) {
                return null;
            }
        }

        return $current;
    }

    /**
     * Get root categories (categories without parent).
     *
     * @param  \Lunar\Models\Channel|null  $channel
     * @param  \Lunar\Models\Language|null  $language
     * @return Collection
     */
    public function getRootCategories($channel = null, $language = null): Collection
    {
        $cacheKey = 'categories.roots';
        if ($channel) {
            $cacheKey .= ".channel.{$channel->id}";
        }
        if ($language) {
            $cacheKey .= ".lang.{$language->id}";
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($channel, $language) {
            $query = Category::whereIsRoot();
            
            if ($channel) {
                $query->visibleInChannel($channel);
            } else {
                $query->active();
            }
            
            if ($language) {
                $query->visibleInLanguage($language);
            }
            
            return $query->ordered()->get();
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
     * @param  \Lunar\Models\Channel|null  $channel
     * @param  \Lunar\Models\Language|null  $language
     * @return Collection
     */
    public function getNavigationCategories(int $maxDepth = 3, $channel = null, $language = null): Collection
    {
        $cacheKey = "categories.navigation.{$maxDepth}";
        if ($channel) {
            $cacheKey .= ".channel.{$channel->id}";
        }
        if ($language) {
            $cacheKey .= ".lang.{$language->id}";
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($maxDepth, $channel, $language) {
            $query = Category::whereIsRoot();
            
            if ($channel) {
                $query->visibleInChannel($channel)->inNavigationForChannel($channel);
            } else {
                $query->active()->inNavigation();
            }
            
            if ($language) {
                $query->visibleInLanguage($language)->inNavigationForLanguage($language);
            }
            
            return $query->ordered()
                ->with(['children' => function ($childQuery) use ($maxDepth, $channel, $language) {
                    if ($channel) {
                        $childQuery->visibleInChannel($channel)->inNavigationForChannel($channel);
                    } else {
                        $childQuery->active()->inNavigation();
                    }
                    
                    if ($language) {
                        $childQuery->visibleInLanguage($language)->inNavigationForLanguage($language);
                    }
                    
                    $childQuery->ordered()->where('depth', '<=', $maxDepth);
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

