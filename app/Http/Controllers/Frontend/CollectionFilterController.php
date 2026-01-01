<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use App\Services\CollectionFilterOptionsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Controller for collection filtering and sorting with AJAX.
 */
class CollectionFilterController extends Controller
{
    /**
     * Get filtered and sorted products for a collection.
     *
     * @param  Request  $request
     * @param  Collection  $collection
     * @return JsonResponse|RedirectResponse
     */
    public function index(Request $request, Collection $collection)
    {
        $query = $collection->products()->published();

        // Apply filters
        $query = $this->applyFilters($query, $request);

        // Apply sorting
        $query = $this->applySorting($query, $request);

        // Get paginated results
        $perPage = $request->get('per_page', 24);
        $products = $query->paginate($perPage);

        // Get available filter options
        $filterOptions = $this->getFilterOptions($collection, $request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
                'filter_options' => $filterOptions,
                'html' => view('frontend.collections._product-grid', [
                    'products' => $products,
                    'collection' => $collection,
                ])->render(),
            ]);
        }

        // Frontend pages are served by Livewire (`CollectionShow`). Keep this legacy URL as a
        // redirect for non-AJAX requests.
        return redirect()->route(
            'frontend.collections.show',
            array_merge(['slug' => (string) $collection->getKey()], $request->query())
        );
    }

    /**
     * Apply filters to query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, Request $request)
    {
        // Price range filter
        if ($request->has('min_price') || $request->has('max_price')) {
            $query = $this->filterByPriceRange($query, $request->get('min_price'), $request->get('max_price'));
        }

        // Availability filter
        if ($request->has('availability')) {
            $query = $this->filterByAvailability($query, $request->get('availability'));
        }

        // Brand filter
        if ($request->has('brands')) {
            $brands = is_array($request->get('brands')) 
                ? $request->get('brands') 
                : explode(',', $request->get('brands'));
            $query->whereHas('brand', function ($q) use ($brands) {
                $q->whereIn('id', array_filter($brands));
            });
        }

        // Category filter
        if ($request->has('categories')) {
            $categories = is_array($request->get('categories')) 
                ? $request->get('categories') 
                : explode(',', $request->get('categories'));
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('id', array_filter($categories));
            });
        }

        // Attribute filters
        if ($request->has('attributes')) {
            $attributes = $request->get('attributes');
            if (is_array($attributes)) {
                foreach ($attributes as $attributeHandle => $values) {
                    if (!empty($values)) {
                        $query = $this->filterByAttribute($query, $attributeHandle, $values);
                    }
                }
            }
        }

        // Rating filter
        if ($request->has('min_rating')) {
            $query->where('average_rating', '>=', $request->get('min_rating'));
        }

        // Search within collection
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("JSON_EXTRACT(attribute_data, '$.name.en') LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_EXTRACT(attribute_data, '$.description.en') LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        return $query;
    }

    /**
     * Filter products by price range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float|null  $minPrice
     * @param  float|null  $maxPrice
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function filterByPriceRange($query, ?float $minPrice, ?float $maxPrice)
    {
        return $query->whereHas('variants', function ($q) use ($minPrice, $maxPrice) {
            $q->whereHas('prices', function ($priceQuery) use ($minPrice, $maxPrice) {
                if ($minPrice !== null) {
                    $priceQuery->where('price', '>=', $minPrice * 100); // Convert to cents
                }
                if ($maxPrice !== null) {
                    $priceQuery->where('price', '<=', $maxPrice * 100); // Convert to cents
                }
            });
        });
    }

    /**
     * Filter products by availability.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $availability
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function filterByAvailability($query, string $availability)
    {
        return match ($availability) {
            'in_stock' => $query->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0);
            }),
            'out_of_stock' => $query->whereHas('variants', function ($q) {
                $q->where('stock', '<=', 0);
            }),
            'low_stock' => $query->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0)
                  ->where('stock', '<=', 10);
            }),
            default => $query,
        };
    }

    /**
     * Filter products by attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $attributeHandle
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function filterByAttribute($query, string $attributeHandle, array $values)
    {
        return $query->whereHas('attributeValues', function ($q) use ($attributeHandle, $values) {
            $q->whereHas('attribute', function ($attrQuery) use ($attributeHandle) {
                $attrQuery->where('handle', $attributeHandle);
            })->whereIn('attribute_value_id', $values);
        });
    }

    /**
     * Apply sorting to query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'default');
        $sortDirection = $request->get('sort_direction', 'asc');

        return match ($sortBy) {
            'price_low' => $this->sortByPrice($query, 'asc'),
            'price_high' => $this->sortByPrice($query, 'desc'),
            'popularity' => $this->sortByPopularity($query),
            'newest' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'rating' => $query->orderBy('average_rating', 'desc')->orderBy('total_reviews', 'desc'),
            'name_asc' => $query->orderByRaw("JSON_EXTRACT(attribute_data, '$.name.en') ASC"),
            'name_desc' => $query->orderByRaw("JSON_EXTRACT(attribute_data, '$.name.en') DESC"),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    /**
     * Sort products by price.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortByPrice($query, string $direction)
    {
        // Join with variants and prices to sort by price
        return $query->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('prices', function ($join) {
                $join->on('product_variants.id', '=', 'prices.priceable_id')
                     ->where('prices.priceable_type', '=', \App\Models\ProductVariant::morphName());
            })
            ->select('products.*')
            ->groupBy('products.id')
            ->orderByRaw("MIN(prices.price) {$direction}");
    }

    /**
     * Sort products by popularity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortByPopularity($query)
    {
        return $query->withCount([
            'orderLines as sales_count' => function ($q) {
                $q->whereHas('order', function ($orderQuery) {
                    $orderQuery->whereNotNull('placed_at');
                });
            }
        ])->orderByDesc('sales_count')
          ->orderByDesc('product_count');
    }

    /**
     * Get available filter options based on current filters.
     *
     * @param  Collection  $collection
     * @param  Request  $request
     * @return array
     */
    public function getFilterOptions(Collection $collection, Request $request)
    {
        return app(CollectionFilterOptionsService::class)->getFilterOptions($collection, $request);
    }

    /**
     * Get price range for products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array
     */
    protected function getPriceRange($query)
    {
        $products = $query->with('variants.prices')->get();
        
        $prices = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                foreach ($variant->prices as $price) {
                    $prices[] = $price->price->decimal;
                }
            }
        }

        if (empty($prices)) {
            return ['min' => 0, 'max' => 0];
        }

        return [
            'min' => min($prices),
            'max' => max($prices),
        ];
    }

    /**
     * Get available brands.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Request  $request
     * @return \Illuminate\Support\Collection
     */
    protected function getAvailableBrands($query, Request $request)
    {
        $tempQuery = clone $query;
        
        // Apply all filters except brand filter
        if ($request->has('brands')) {
            // Don't apply brand filter when getting brand options
        }

        return $tempQuery->with('brand')
            ->get()
            ->pluck('brand')
            ->filter()
            ->unique('id')
            ->map(function ($brand) use ($query) {
                $count = $query->where('brand_id', $brand->id)->count();
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'count' => $count,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * Get available categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Request  $request
     * @return \Illuminate\Support\Collection
     */
    protected function getAvailableCategories($query, Request $request)
    {
        $tempQuery = clone $query;
        
        return $tempQuery->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->unique('id')
            ->map(function ($category) use ($query) {
                $count = $query->whereHas('categories', function ($q) use ($category) {
                    $q->where('categories.id', $category->id);
                })->count();
                return [
                    'id' => $category->id,
                    'name' => $category->translateAttribute('name'),
                    'count' => $count,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * Get available attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Request  $request
     * @return array
     */
    protected function getAvailableAttributes($query, Request $request)
    {
        $tempQuery = clone $query;
        
        $products = $tempQuery->with('attributeValues.attribute', 'attributeValues.value')->get();
        
        $attributes = [];
        
        foreach ($products as $product) {
            foreach ($product->attributeValues as $attributeValue) {
                $attribute = $attributeValue->attribute;
                $value = $attributeValue->value;
                
                if (!$attribute || !$value) {
                    continue;
                }

                $handle = $attribute->handle;
                
                if (!isset($attributes[$handle])) {
                    $attributes[$handle] = [
                        'handle' => $handle,
                        'name' => $attribute->translateAttribute('name'),
                        'type' => $attribute->type,
                        'values' => [],
                    ];
                }

                $valueId = $value->id;
                if (!isset($attributes[$handle]['values'][$valueId])) {
                    $count = $query->whereHas('attributeValues', function ($q) use ($attribute, $value) {
                        $q->where('attribute_id', $attribute->id)
                          ->where('attribute_value_id', $value->id);
                    })->count();
                    
                    $attributes[$handle]['values'][$valueId] = [
                        'id' => $valueId,
                        'name' => $value->translateAttribute('name'),
                        'count' => $count,
                    ];
                }
            }
        }

        return array_values($attributes);
    }

    /**
     * Get availability counts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Request  $request
     * @return array
     */
    protected function getAvailabilityCounts($query, Request $request)
    {
        $tempQuery = clone $query;
        
        return [
            'in_stock' => (clone $tempQuery)->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0);
            })->count(),
            'out_of_stock' => (clone $tempQuery)->whereHas('variants', function ($q) {
                $q->where('stock', '<=', 0);
            })->count(),
            'low_stock' => (clone $tempQuery)->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0)->where('stock', '<=', 10);
            })->count(),
        ];
    }
}


