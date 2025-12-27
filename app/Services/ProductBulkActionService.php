<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBulkAction;
use App\Jobs\ProcessBulkAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

/**
 * Service for managing bulk actions on products.
 */
class ProductBulkActionService
{
    /**
     * Execute bulk action.
     *
     * @param  string  $actionType
     * @param  array  $filters
     * @param  array  $parameters
     * @param  bool  $queue
     * @return ProductBulkAction
     */
    public function execute(
        string $actionType,
        array $filters = [],
        array $parameters = [],
        bool $queue = true
    ): ProductBulkAction {
        // Get products based on filters
        $products = $this->getProductsByFilters($filters);
        
        // Create bulk action record
        $bulkAction = ProductBulkAction::create([
            'user_id' => Auth::id(),
            'action_type' => $actionType,
            'filters' => $filters,
            'parameters' => $parameters,
            'status' => 'pending',
            'total_products' => $products->count(),
            'product_ids' => $products->pluck('id')->toArray(),
        ]);
        
        if ($queue) {
            // Queue the job
            ProcessBulkAction::dispatch($bulkAction);
        } else {
            // Execute immediately
            $this->processBulkAction($bulkAction);
        }
        
        return $bulkAction;
    }

    /**
     * Process bulk action.
     *
     * @param  ProductBulkAction  $bulkAction
     * @return void
     */
    public function processBulkAction(ProductBulkAction $bulkAction): void
    {
        $bulkAction->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
        
        $products = Product::whereIn('id', $bulkAction->product_ids ?? [])->get();
        $successful = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($products as $product) {
            try {
                $this->executeAction($product, $bulkAction->action_type, $bulkAction->parameters);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ];
            }
            
            $bulkAction->update([
                'processed_products' => $successful + $failed,
                'successful_products' => $successful,
                'failed_products' => $failed,
            ]);
        }
        
        $bulkAction->update([
            'status' => 'completed',
            'completed_at' => now(),
            'errors' => $errors,
        ]);
    }

    /**
     * Execute action on product.
     *
     * @param  Product  $product
     * @param  string  $actionType
     * @param  array  $parameters
     * @return void
     */
    protected function executeAction(Product $product, string $actionType, array $parameters): void
    {
        $workflowService = app(ProductWorkflowService::class);
        
        switch ($actionType) {
            case 'publish':
                $workflowService->publish($product);
                break;
                
            case 'unpublish':
                $workflowService->unpublish($product);
                break;
                
            case 'archive':
                $workflowService->archive($product);
                break;
                
            case 'unarchive':
                $workflow = $workflowService->getOrCreateWorkflow($product);
                $workflow->update(['status' => 'draft']);
                $product->update(['status' => 'draft']);
                break;
                
            case 'delete':
                $product->delete();
                break;
                
            case 'update_status':
                $product->update(['status' => $parameters['status'] ?? 'draft']);
                break;
                
            case 'update_price':
                $this->updatePrice($product, $parameters);
                break;
                
            case 'update_stock':
                $this->updateStock($product, $parameters);
                break;
                
            case 'assign_category':
                $product->categories()->syncWithoutDetaching([$parameters['category_id']]);
                break;
                
            case 'assign_collection':
                $product->collections()->syncWithoutDetaching([$parameters['collection_id']]);
                break;
                
            case 'assign_tag':
                // Assuming tags relationship exists
                if (method_exists($product, 'tags')) {
                    $product->tags()->syncWithoutDetaching([$parameters['tag_id']]);
                }
                break;
                
            case 'remove_category':
                $product->categories()->detach($parameters['category_id']);
                break;
                
            case 'remove_collection':
                $product->collections()->detach($parameters['collection_id']);
                break;
                
            case 'remove_tag':
                if (method_exists($product, 'tags')) {
                    $product->tags()->detach($parameters['tag_id']);
                }
                break;
                
            default:
                throw new \Exception("Unknown action type: {$actionType}");
        }
    }

    /**
     * Update product price.
     *
     * @param  Product  $product
     * @param  array  $parameters
     * @return void
     */
    protected function updatePrice(Product $product, array $parameters): void
    {
        $variant = $product->variants->first();
        if (!$variant) {
            throw new \Exception('Product has no variants');
        }
        
        $currencyId = $parameters['currency_id'] ?? \Lunar\Models\Currency::getDefault()->id;
        $price = $parameters['price'] ?? null;
        $priceType = $parameters['price_type'] ?? 'default';
        
        if ($price === null) {
            throw new \Exception('Price is required');
        }
        
        // Convert to integer (cents)
        $priceInCents = is_numeric($price) ? (int)($price * 100) : $price;
        
        $variant->prices()->updateOrCreate(
            [
                'currency_id' => $currencyId,
                'price_type' => $priceType,
            ],
            [
                'price' => $priceInCents,
            ]
        );
    }

    /**
     * Update product stock.
     *
     * @param  Product  $product
     * @param  array  $parameters
     * @return void
     */
    protected function updateStock(Product $product, array $parameters): void
    {
        $variant = $product->variants->first();
        if (!$variant) {
            throw new \Exception('Product has no variants');
        }
        
        $stock = $parameters['stock'] ?? null;
        $operation = $parameters['operation'] ?? 'set'; // set, add, subtract
        
        if ($stock === null) {
            throw new \Exception('Stock value is required');
        }
        
        switch ($operation) {
            case 'set':
                $variant->update(['stock' => (int)$stock]);
                break;
            case 'add':
                $variant->increment('stock', (int)$stock);
                break;
            case 'subtract':
                $variant->decrement('stock', (int)$stock);
                break;
        }
    }

    /**
     * Get products by filters.
     *
     * @param  array  $filters
     * @return Collection
     */
    protected function getProductsByFilters(array $filters): Collection
    {
        $query = Product::query();
        
        // Filter by IDs
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }
        
        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Filter by category
        if (isset($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }
        
        // Filter by collection
        if (isset($filters['collection_id'])) {
            $query->whereHas('collections', function ($q) use ($filters) {
                $q->where('collections.id', $filters['collection_id']);
            });
        }
        
        // Filter by brand
        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        
        // Filter by SKU pattern
        if (isset($filters['sku_pattern'])) {
            $query->where('sku', 'like', "%{$filters['sku_pattern']}%");
        }
        
        // Filter by date range
        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }
        
        return $query->get();
    }
}

