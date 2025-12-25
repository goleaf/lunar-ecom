<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Product;
use App\Models\SmartCollectionRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing smart collection rules and auto-assignment.
 */
class SmartCollectionService
{
    /**
     * Process smart collection rules and assign products.
     *
     * @param  Collection  $collection
     * @return int Number of products assigned
     */
    public function processSmartCollection(Collection $collection): int
    {
        if (!$collection->auto_assign) {
            return 0;
        }

        $rules = $collection->smartRules()->active()->orderBy('priority')->get();
        
        if ($rules->isEmpty()) {
            return 0;
        }

        // Build query based on rules
        $query = Product::where('status', 'published');
        $query = $this->applyRules($query, $rules);

        // Apply max products limit if set
        if ($collection->max_products) {
            $products = $query->limit($collection->max_products)->get();
        } else {
            $products = $query->get();
        }

        // Remove existing auto-assigned products
        DB::table(config('lunar.database.table_prefix') . 'collection_product_metadata')
            ->where('collection_id', $collection->id)
            ->where('is_auto_assigned', true)
            ->delete();

        // Assign new products
        $assigned = 0;
        foreach ($products as $index => $product) {
            DB::table(config('lunar.database.table_prefix') . 'collection_product_metadata')
                ->insert([
                    'collection_id' => $collection->id,
                    'product_id' => $product->id,
                    'is_auto_assigned' => true,
                    'position' => $index,
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $assigned++;
        }

        // Update collection
        $collection->update([
            'product_count' => $assigned,
            'last_updated_at' => now(),
        ]);

        return $assigned;
    }

    /**
     * Apply rules to query builder.
     *
     * @param  Builder  $query
     * @param  \Illuminate\Database\Eloquent\Collection  $rules
     * @return Builder
     */
    public function applyRules(Builder $query, $rules): Builder
    {
        // Group rules by logic group
        $groupedRules = $rules->groupBy('logic_group');
        
        // If no groups, treat all rules as AND
        if ($groupedRules->keys()->first() === null) {
            foreach ($rules as $rule) {
                $query = $this->applyRule($query, $rule);
            }
            return $query;
        }

        // Apply grouped rules
        $hasOrGroups = false;
        foreach ($groupedRules as $groupKey => $groupRules) {
            if ($groupRules->first()->group_operator === 'or') {
                $hasOrGroups = true;
                break;
            }
        }

        if ($hasOrGroups) {
            // Complex OR grouping - apply each group separately and union
            $query->where(function ($q) use ($groupedRules) {
                foreach ($groupedRules as $groupKey => $groupRules) {
                    $q->orWhere(function ($subQuery) use ($groupRules) {
                        foreach ($groupRules as $rule) {
                            $this->applyRule($subQuery, $rule, 'and');
                        }
                    });
                }
            });
        } else {
            // All AND - apply sequentially
            foreach ($rules as $rule) {
                $query = $this->applyRule($query, $rule);
            }
        }

        return $query;
    }

    /**
     * Apply a single rule to query.
     *
     * @param  Builder  $query
     * @param  SmartCollectionRule  $rule
     * @param  string  $defaultOperator
     * @return Builder
     */
    protected function applyRule(Builder $query, SmartCollectionRule $rule, string $defaultOperator = 'and'): Builder
    {
        return match ($rule->field) {
            'price' => $this->applyPriceRule($query, $rule),
            'tag' => $this->applyTagRule($query, $rule),
            'product_type' => $this->applyProductTypeRule($query, $rule),
            'inventory_status' => $this->applyInventoryStatusRule($query, $rule),
            'brand' => $this->applyBrandRule($query, $rule),
            'category' => $this->applyCategoryRule($query, $rule),
            'attribute' => $this->applyAttributeRule($query, $rule),
            'rating' => $this->applyRatingRule($query, $rule),
            'created_at', 'updated_at' => $this->applyDateRule($query, $rule),
            default => $query,
        };
    }

    /**
     * Apply price rule.
     */
    protected function applyPriceRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        return $query->whereHas('variants', function ($q) use ($rule) {
            $q->whereHas('prices', function ($priceQuery) use ($rule) {
                $value = $rule->value;
                
                match ($rule->operator) {
                    'greater_than' => $priceQuery->where('price', '>', ($value * 100)),
                    'less_than' => $priceQuery->where('price', '<', ($value * 100)),
                    'equals' => $priceQuery->where('price', '=', ($value * 100)),
                    'between' => $priceQuery->whereBetween('price', [
                        ($value['min'] ?? 0) * 100,
                        ($value['max'] ?? PHP_INT_MAX) * 100
                    ]),
                    default => $priceQuery,
                };
            });
        });
    }

    /**
     * Apply tag rule.
     */
    protected function applyTagRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        $value = is_array($rule->value) ? $rule->value : [$rule->value];
        
        return match ($rule->operator) {
            'equals' => $query->whereHas('tags', fn($q) => $q->where('name', $rule->value)),
            'not_equals' => $query->whereDoesntHave('tags', fn($q) => $q->where('name', $rule->value)),
            'in' => $query->whereHas('tags', fn($q) => $q->whereIn('name', $value)),
            'not_in' => $query->whereDoesntHave('tags', fn($q) => $q->whereIn('name', $value)),
            'contains' => $query->whereHas('tags', fn($q) => $q->where('name', 'like', "%{$rule->value}%")),
            default => $query,
        };
    }

    /**
     * Apply product type rule.
     */
    protected function applyProductTypeRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        $value = is_array($rule->value) ? $rule->value : [$rule->value];
        
        return match ($rule->operator) {
            'equals' => $query->where('product_type_id', $rule->value),
            'not_equals' => $query->where('product_type_id', '!=', $rule->value),
            'in' => $query->whereIn('product_type_id', $value),
            'not_in' => $query->whereNotIn('product_type_id', $value),
            default => $query,
        };
    }

    /**
     * Apply inventory status rule.
     */
    protected function applyInventoryStatusRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        return match ($rule->operator) {
            'equals' => match ($rule->value) {
                'in_stock' => $query->whereHas('variants', fn($q) => $q->where('stock', '>', 0)),
                'out_of_stock' => $query->whereHas('variants', fn($q) => $q->where('stock', '<=', 0)),
                'low_stock' => $query->whereHas('variants', fn($q) => $q->where('stock', '>', 0)->where('stock', '<=', 10)),
                'backorder' => $query->whereHas('variants', fn($q) => $q->where('backorder', true)),
                default => $query,
            },
            'not_equals' => match ($rule->value) {
                'in_stock' => $query->whereDoesntHave('variants', fn($q) => $q->where('stock', '>', 0)),
                'out_of_stock' => $query->whereDoesntHave('variants', fn($q) => $q->where('stock', '<=', 0)),
                'low_stock' => $query->whereDoesntHave('variants', fn($q) => $q->where('stock', '>', 0)->where('stock', '<=', 10)),
                default => $query,
            },
            default => $query,
        };
    }

    /**
     * Apply brand rule.
     */
    protected function applyBrandRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        $value = is_array($rule->value) ? $rule->value : [$rule->value];
        
        return match ($rule->operator) {
            'equals' => $query->where('brand_id', $rule->value),
            'not_equals' => $query->where('brand_id', '!=', $rule->value),
            'in' => $query->whereIn('brand_id', $value),
            'not_in' => $query->whereNotIn('brand_id', $value),
            default => $query,
        };
    }

    /**
     * Apply category rule.
     */
    protected function applyCategoryRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        $value = is_array($rule->value) ? $rule->value : [$rule->value];
        
        return match ($rule->operator) {
            'equals' => $query->whereHas('categories', fn($q) => $q->where('id', $rule->value)),
            'not_equals' => $query->whereDoesntHave('categories', fn($q) => $q->where('id', $rule->value)),
            'in' => $query->whereHas('categories', fn($q) => $q->whereIn('id', $value)),
            'not_in' => $query->whereDoesntHave('categories', fn($q) => $q->whereIn('id', $value)),
            default => $query,
        };
    }

    /**
     * Apply attribute rule.
     */
    protected function applyAttributeRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        // Value format: ['attribute_handle' => 'color', 'values' => [1, 2, 3]]
        $attributeHandle = $rule->value['attribute_handle'] ?? null;
        $values = $rule->value['values'] ?? [];
        
        if (!$attributeHandle || empty($values)) {
            return $query;
        }

        $values = is_array($values) ? $values : [$values];
        
        return match ($rule->operator) {
            'equals', 'in' => $query->whereHas('attributeValues', function ($q) use ($attributeHandle, $values) {
                $q->whereHas('attribute', fn($attrQ) => $attrQ->where('handle', $attributeHandle))
                  ->whereIn('attribute_value_id', $values);
            }),
            'not_equals', 'not_in' => $query->whereDoesntHave('attributeValues', function ($q) use ($attributeHandle, $values) {
                $q->whereHas('attribute', fn($attrQ) => $attrQ->where('handle', $attributeHandle))
                  ->whereIn('attribute_value_id', $values);
            }),
            default => $query,
        };
    }

    /**
     * Apply rating rule.
     */
    protected function applyRatingRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        $value = $rule->value;
        
        return match ($rule->operator) {
            'greater_than' => $query->where('average_rating', '>', $value),
            'less_than' => $query->where('average_rating', '<', $value),
            'equals' => $query->where('average_rating', '=', $value),
            'between' => $query->whereBetween('average_rating', [$value['min'] ?? 0, $value['max'] ?? 5]),
            default => $query,
        };
    }

    /**
     * Apply date rule.
     */
    protected function applyDateRule(Builder $query, SmartCollectionRule $rule): Builder
    {
        $value = $rule->value;
        $field = $rule->field;
        
        return match ($rule->operator) {
            'greater_than' => $query->where($field, '>', $value),
            'less_than' => $query->where($field, '<', $value),
            'equals' => $query->whereDate($field, '=', $value),
            'between' => $query->whereBetween($field, [$value['from'] ?? now(), $value['to'] ?? now()]),
            default => $query,
        };
    }

    /**
     * Process all smart collections.
     *
     * @return void
     */
    public function processAllSmartCollections(): void
    {
        $collections = Collection::where('auto_assign', true)
            ->where('collection_type', 'custom')
            ->get();

        foreach ($collections as $collection) {
            try {
                $count = $this->processSmartCollection($collection);
                Log::info("Processed smart collection '{$collection->name}' (ID: {$collection->id}). Assigned {$count} products.");
            } catch (\Exception $e) {
                Log::error("Failed to process smart collection '{$collection->name}' (ID: {$collection->id}): {$e->getMessage()}");
            }
        }
    }
}

