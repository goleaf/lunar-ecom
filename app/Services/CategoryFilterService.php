<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Service for filtering products by categories with AND/OR logic.
 */
class CategoryFilterService
{
    /**
     * Filter products by categories with AND logic (product must be in ALL categories).
     *
     * @param  Builder  $query
     * @param  array|Collection  $categoryIds
     * @return Builder
     */
    public function filterByCategoriesAnd(Builder $query, $categoryIds): Builder
    {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        if (empty($categoryIds)) {
            return $query;
        }

        // Product must be in all specified categories
        foreach ($categoryIds as $categoryId) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        return $query;
    }

    /**
     * Filter products by categories with OR logic (product must be in ANY category).
     *
     * @param  Builder  $query
     * @param  array|Collection  $categoryIds
     * @return Builder
     */
    public function filterByCategoriesOr(Builder $query, $categoryIds): Builder
    {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        if (empty($categoryIds)) {
            return $query;
        }

        // Product must be in at least one of the specified categories
        $query->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });

        return $query;
    }

    /**
     * Filter products by category and include products from descendant categories.
     *
     * @param  Builder  $query
     * @param  Category|int  $category
     * @param  bool  $includeDescendants
     * @return Builder
     */
    public function filterByCategory(
        Builder $query,
        $category,
        bool $includeDescendants = true
    ): Builder {
        $category = $this->resolveCategory($category);

        if (!$category) {
            return $query;
        }

        $categoryIds = $includeDescendants
            ? $category->descendants()->pluck('id')->push($category->id)
            : collect([$category->id]);

        return $query->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });
    }

    /**
     * Filter products by multiple categories with mixed AND/OR logic.
     *
     * @param  Builder  $query
     * @param  array  $filters  Array of category filters with 'logic' and 'categories' keys
     * @return Builder
     */
    public function filterByMixedLogic(Builder $query, array $filters): Builder
    {
        if (empty($filters)) {
            return $query;
        }

        $query->where(function ($q) use ($filters) {
            foreach ($filters as $filter) {
                $logic = $filter['logic'] ?? 'or'; // 'and' or 'or'
                $categoryIds = $this->normalizeCategoryIds($filter['categories'] ?? []);

                if (empty($categoryIds)) {
                    continue;
                }

                if ($logic === 'and') {
                    $q->where(function ($subQuery) use ($categoryIds) {
                        foreach ($categoryIds as $categoryId) {
                            $subQuery->whereHas('categories', function ($catQuery) use ($categoryId) {
                                $catQuery->where('categories.id', $categoryId);
                            });
                        }
                    });
                } else {
                    $q->orWhereHas('categories', function ($catQuery) use ($categoryIds) {
                        $catQuery->whereIn('categories.id', $categoryIds);
                    });
                }
            }
        });

        return $query;
    }

    /**
     * Exclude products from specific categories.
     *
     * @param  Builder  $query
     * @param  array|Collection  $categoryIds
     * @return Builder
     */
    public function excludeCategories(Builder $query, $categoryIds): Builder
    {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        if (empty($categoryIds)) {
            return $query;
        }

        return $query->whereDoesntHave('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });
    }

    /**
     * Normalize category IDs to array.
     *
     * @param  mixed  $categoryIds
     * @return array
     */
    protected function normalizeCategoryIds($categoryIds): array
    {
        if ($categoryIds instanceof Collection) {
            return $categoryIds->toArray();
        }

        if (is_array($categoryIds)) {
            return $categoryIds;
        }

        if (is_numeric($categoryIds)) {
            return [$categoryIds];
        }

        return [];
    }

    /**
     * Resolve category from ID or model.
     *
     * @param  Category|int  $category
     * @return Category|null
     */
    protected function resolveCategory($category): ?Category
    {
        if ($category instanceof Category) {
            return $category;
        }

        if (is_numeric($category)) {
            return Category::find($category);
        }

        return null;
    }
}

