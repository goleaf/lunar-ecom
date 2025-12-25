<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAutomationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing automated product rules.
 */
class ProductAutomationService
{
    /**
     * Process all due automation rules.
     *
     * @return int  Number of rules processed
     */
    public function processDueRules(): int
    {
        $rules = ProductAutomationRule::due()->orderBy('priority')->get();
        $processed = 0;
        
        foreach ($rules as $rule) {
            try {
                $this->executeRule($rule);
                $rule->increment('execution_count');
                $rule->update(['last_executed_at' => now()]);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to execute automation rule', [
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $processed;
    }

    /**
     * Execute a specific rule.
     *
     * @param  ProductAutomationRule  $rule
     * @return void
     */
    public function executeRule(ProductAutomationRule $rule): void
    {
        $products = $this->getProductsForRule($rule);
        
        foreach ($products as $product) {
            if ($this->checkConditions($product, $rule)) {
                $this->executeActions($product, $rule);
            }
        }
    }

    /**
     * Get products for rule based on scope.
     *
     * @param  ProductAutomationRule  $rule
     * @return Collection
     */
    protected function getProductsForRule(ProductAutomationRule $rule): Collection
    {
        $query = Product::query();
        
        switch ($rule->scope) {
            case 'all':
                // All products
                break;
                
            case 'category':
                if (isset($rule->scope_filters['category_ids'])) {
                    $query->whereHas('categories', function ($q) use ($rule) {
                        $q->whereIn('categories.id', $rule->scope_filters['category_ids']);
                    });
                }
                break;
                
            case 'collection':
                if (isset($rule->scope_filters['collection_ids'])) {
                    $query->whereHas('collections', function ($q) use ($rule) {
                        $q->whereIn('collections.id', $rule->scope_filters['collection_ids']);
                    });
                }
                break;
                
            case 'brand':
                if (isset($rule->scope_filters['brand_ids'])) {
                    $query->whereIn('brand_id', $rule->scope_filters['brand_ids']);
                }
                break;
                
            case 'tag':
                // Assuming tags relationship exists
                if (isset($rule->scope_filters['tag_ids']) && method_exists(Product::class, 'tags')) {
                    $query->whereHas('tags', function ($q) use ($rule) {
                        $q->whereIn('tags.id', $rule->scope_filters['tag_ids']);
                    });
                }
                break;
        }
        
        return $query->get();
    }

    /**
     * Check if product meets rule conditions.
     *
     * @param  Product  $product
     * @param  ProductAutomationRule  $rule
     * @return bool
     */
    protected function checkConditions(Product $product, ProductAutomationRule $rule): bool
    {
        $conditions = $rule->conditions ?? [];
        
        if (empty($conditions)) {
            return true;
        }
        
        foreach ($conditions as $condition) {
            if (!$this->checkCondition($product, $condition)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check a single condition.
     *
     * @param  Product  $product
     * @param  array  $condition
     * @return bool
     */
    protected function checkCondition(Product $product, array $condition): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;
        
        if (!$field) {
            return true;
        }
        
        $productValue = $this->getProductFieldValue($product, $field);
        
        return match($operator) {
            'equals' => $productValue == $value,
            'not_equals' => $productValue != $value,
            'greater_than' => $productValue > $value,
            'less_than' => $productValue < $value,
            'greater_or_equal' => $productValue >= $value,
            'less_or_equal' => $productValue <= $value,
            'contains' => str_contains((string)$productValue, (string)$value),
            'in' => in_array($productValue, (array)$value),
            'not_in' => !in_array($productValue, (array)$value),
            default => true,
        };
    }

    /**
     * Get product field value.
     *
     * @param  Product  $product
     * @param  string  $field
     * @return mixed
     */
    protected function getProductFieldValue(Product $product, string $field)
    {
        return match($field) {
            'stock' => $product->variants->sum('stock'),
            'price' => $product->variants->first()?->prices()->first()?->price->decimal,
            'status' => $product->status,
            'sku' => $product->sku,
            default => $product->getAttribute($field),
        };
    }

    /**
     * Execute actions on product.
     *
     * @param  Product  $product
     * @param  ProductAutomationRule  $rule
     * @return void
     */
    protected function executeActions(Product $product, ProductAutomationRule $rule): void
    {
        $actions = $rule->actions ?? [];
        $workflowService = app(ProductWorkflowService::class);
        
        foreach ($actions as $action) {
            $actionType = $action['type'] ?? null;
            
            if (!$actionType) {
                continue;
            }
            
            try {
                match($actionType) {
                    'archive' => $workflowService->archive($product),
                    'publish' => $workflowService->publish($product),
                    'unpublish' => $workflowService->unpublish($product),
                    'set_status' => $product->update(['status' => $action['status'] ?? 'draft']),
                    'update_price' => $this->updateProductPrice($product, $action),
                    'notify' => $this->sendNotification($product, $action),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error('Failed to execute action on product', [
                    'product_id' => $product->id,
                    'action' => $actionType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update product price.
     *
     * @param  Product  $product
     * @param  array  $action
     * @return void
     */
    protected function updateProductPrice(Product $product, array $action): void
    {
        $variant = $product->variants->first();
        if (!$variant) {
            return;
        }
        
        $price = $action['price'] ?? null;
        $currencyId = $action['currency_id'] ?? \Lunar\Facades\Currency::getDefault()->id;
        
        if ($price === null) {
            return;
        }
        
        $priceInCents = (int)($price * 100);
        
        $variant->prices()->updateOrCreate(
            ['currency_id' => $currencyId],
            ['price' => $priceInCents]
        );
    }

    /**
     * Send notification.
     *
     * @param  Product  $product
     * @param  array  $action
     * @return void
     */
    protected function sendNotification(Product $product, array $action): void
    {
        // Implement notification logic
        // This would integrate with your notification system
        Log::info('Product automation notification', [
            'product_id' => $product->id,
            'action' => $action,
        ]);
    }

    /**
     * Create automation rule.
     *
     * @param  array  $data
     * @return ProductAutomationRule
     */
    public function createRule(array $data): ProductAutomationRule
    {
        return ProductAutomationRule::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger_type' => $data['trigger_type'],
            'conditions' => $data['conditions'] ?? [],
            'actions' => $data['actions'] ?? [],
            'scope' => $data['scope'] ?? 'all',
            'scope_filters' => $data['scope_filters'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'priority' => $data['priority'] ?? 0,
        ]);
    }
}

