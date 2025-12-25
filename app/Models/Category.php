<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Cache;

/**
 * Category model with nested set pattern for unlimited depth hierarchy.
 * 
 * Features:
 * - Nested set pattern for efficient tree queries
 * - Translatable name and description
 * - SEO-friendly slugs with full path
 * - Media Library integration for images
 * - Cached product counts
 * - Breadcrumb generation
 */
class Category extends Model implements HasMedia
{
    use HasFactory, NodeTrait, SoftDeletes, InteractsWithMedia;

    /**
     * The table associated with the model.
     * Uses Lunar's table prefix.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'categories';
    }

    /**
     * Get the parent key name.
     *
     * @return string
     */
    public function getParentIdName()
    {
        return 'parent_id';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'display_order',
        'is_active',
        'show_in_navigation',
        'icon',
        'product_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'show_in_navigation' => 'boolean',
        'product_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = static::generateSlug($category);
            }
        });

        // Update slug if name changes
        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = static::generateSlug($category, $category->id);
            }
        });

        // Update product counts when category is saved
        static::saved(function ($category) {
            if (!$category->isDirty('product_count')) {
                $category->updateProductCount();
            }
        });

        // Clear cache when category is deleted
        static::deleted(function ($category) {
            Cache::forget("category.{$category->id}.breadcrumb");
            Cache::forget("category.{$category->id}.full_path");
            $category->clearAncestorsCache();
        });
    }

    /**
     * Generate a unique slug for the category.
     *
     * @param  Category  $category
     * @param  int|null  $excludeId
     * @return string
     */
    protected static function generateSlug(Category $category, ?int $excludeId = null): string
    {
        $name = is_array($category->name) 
            ? ($category->name[app()->getLocale()] ?? reset($category->name))
            : $category->name;
        
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Build full path slug if has parent
        if ($category->parent_id) {
            $parent = static::find($category->parent_id);
            if ($parent) {
                $parentPath = $parent->getFullPath();
                $slug = $parentPath . '/' . $baseSlug;
            }
        }

        // Ensure uniqueness
        while (static::where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            if ($category->parent_id) {
                $parent = static::find($category->parent_id);
                if ($parent) {
                    $parentPath = $parent->getFullPath();
                    $slug = $parentPath . '/' . $baseSlug . '-' . $counter;
                }
            }
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the translatable name for current locale.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function getName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $name = $this->name;
        
        if (is_array($name)) {
            return $name[$locale] ?? $name[array_key_first($name)] ?? '';
        }
        
        return $name ?? '';
    }

    /**
     * Get the translatable description for current locale.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    public function getDescription(?string $locale = null): ?string
    {
        if (!$this->description) {
            return null;
        }

        $locale = $locale ?? app()->getLocale();
        $description = $this->description;
        
        if (is_array($description)) {
            return $description[$locale] ?? $description[array_key_first($description)] ?? null;
        }
        
        return $description;
    }

    /**
     * Get full path slug (includes all ancestors).
     *
     * @return string
     */
    public function getFullPath(): string
    {
        return Cache::remember("category.{$this->id}.full_path", 3600, function () {
            $ancestors = $this->ancestors()->get();
            $path = [];
            
            foreach ($ancestors as $ancestor) {
                $ancestorName = is_array($ancestor->name) 
                    ? ($ancestor->name[app()->getLocale()] ?? reset($ancestor->name))
                    : $ancestor->name;
                $path[] = Str::slug($ancestorName);
            }
            
            $currentName = is_array($this->name) 
                ? ($this->name[app()->getLocale()] ?? reset($this->name))
                : $this->name;
            $path[] = Str::slug($currentName);
            
            return implode('/', $path);
        });
    }

    /**
     * Get breadcrumb trail.
     *
     * @return array
     */
    public function getBreadcrumb(): array
    {
        return Cache::remember("category.{$this->id}.breadcrumb", 3600, function () {
            $breadcrumb = [];
            $ancestors = $this->ancestors()->get();
            
            foreach ($ancestors as $ancestor) {
                $breadcrumb[] = [
                    'id' => $ancestor->id,
                    'name' => $ancestor->getName(),
                    'slug' => $ancestor->slug,
                    'url' => url('/categories/' . $ancestor->getFullPath()),
                ];
            }
            
            // Add current category
            $breadcrumb[] = [
                'id' => $this->id,
                'name' => $this->getName(),
                'slug' => $this->slug,
                'url' => url('/categories/' . $this->getFullPath()),
            ];
            
            return $breadcrumb;
        });
    }

    /**
     * Get ancestors (all parent categories up to root).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAncestors()
    {
        return $this->ancestors;
    }

    /**
     * Get descendants (all child categories recursively).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDescendants()
    {
        return $this->descendants;
    }

    /**
     * Get siblings (categories with same parent).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSiblings()
    {
        return $this->siblings()->defaultOrder()->get();
    }

    /**
     * Get children (direct child categories only).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChildren()
    {
        return $this->children()->defaultOrder()->get();
    }

    /**
     * Get parent category.
     *
     * @return Category|null
     */
    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * Products relationship.
     *
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            config('lunar.database.table_prefix') . 'category_product',
            'category_id',
            'product_id'
        )->withPivot('position')
          ->withTimestamps()
          ->orderByPivot('position');
    }

    /**
     * Get all products including from descendant categories.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllProducts()
    {
        $categoryIds = $this->descendants()->pluck('id')->push($this->id);
        
        return Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->get();
    }

    /**
     * Update product count cache.
     *
     * @return void
     */
    public function updateProductCount(): void
    {
        $count = $this->products()->count();
        $this->product_count = $count;
        $this->saveQuietly(); // Use saveQuietly to avoid triggering events
        
        // Update ancestors' counts
        $this->ancestors->each(function ($ancestor) {
            $ancestor->updateProductCount();
        });
    }

    /**
     * Attach products to category and update counts.
     *
     * @param  array|Collection  $productIds
     * @param  array  $pivotData
     * @return void
     */
    public function attachProducts($productIds, array $pivotData = []): void
    {
        $this->products()->syncWithoutDetaching(
            is_array($productIds) 
                ? array_fill_keys($productIds, $pivotData)
                : $productIds->mapWithKeys(fn($id) => [$id => $pivotData])->toArray()
        );
        
        $this->updateProductCount();
    }

    /**
     * Detach products from category and update counts.
     *
     * @param  array|Collection  $productIds
     * @return void
     */
    public function detachProducts($productIds): void
    {
        $this->products()->detach($productIds);
        $this->updateProductCount();
    }

    /**
     * Clear cache for this category and ancestors.
     *
     * @return void
     */
    protected function clearAncestorsCache(): void
    {
        $this->ancestors->each(function ($ancestor) {
            Cache::forget("category.{$ancestor->id}.breadcrumb");
            Cache::forget("category.{$ancestor->id}.full_path");
        });
    }

    /**
     * Register media collections.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        // Media collections are registered via CategoryMediaDefinitions
        // This method is kept for backward compatibility
    }

    /**
     * Get category image URL.
     *
     * @param  string|null  $conversion
     * @return string|null
     */
    public function getImageUrl(?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia('image');
        
        if (!$media) {
            return null;
        }

        return $conversion 
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include categories shown in navigation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInNavigation($query)
    {
        return $query->where('show_in_navigation', true);
    }

    /**
     * Scope a query to order by display order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}

