<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for Product Core Model business logic.
 * 
 * Handles complex operations like duplicate/clone, publish scheduling,
 * product locking, and version management.
 */
class ProductCoreService
{
    /**
     * Duplicate/clone a product with all relationships.
     *
     * @param  Product  $product
     * @param  string|null  $newName
     * @param  array  $options
     * @return Product
     */
    public function duplicateProduct(Product $product, ?string $newName = null, array $options = []): Product
    {
        if ($product->is_locked && !($options['force'] ?? false)) {
            throw ValidationException::withMessages([
                'product' => ['Cannot duplicate locked product. Reason: ' . ($product->lock_reason ?? 'No reason provided')]
            ]);
        }

        return DB::transaction(function () use ($product, $newName, $options) {
            return $product->duplicate($newName);
        });
    }

    /**
     * Lock product if it has live orders.
     *
     * @param  Product  $product
     * @param  string|null  $reason
     * @return Product
     */
    public function lockIfHasLiveOrders(Product $product, ?string $reason = null): Product
    {
        // Check if product has any orders with statuses that indicate "live" orders
        $hasLiveOrders = DB::table('lunar_orders')
            ->join('lunar_order_lines', 'lunar_orders.id', '=', 'lunar_order_lines.order_id')
            ->join('lunar_product_variants', 'lunar_order_lines.purchasable_id', '=', 'lunar_product_variants.id')
            ->where('lunar_product_variants.product_id', $product->id)
            ->whereIn('lunar_orders.status', ['pending', 'processing', 'shipped', 'partially_shipped'])
            ->exists();

        if ($hasLiveOrders && !$product->is_locked) {
            $product->lock($reason ?? 'Product has live orders', auth()->user());
        }

        return $product;
    }

    /**
     * Process scheduled publish/unpublish actions.
     *
     * @return int Number of products processed
     */
    public function processScheduledPublishes(): int
    {
        $count = 0;

        // Process products scheduled for publish
        $productsToPublish = Product::scheduledForPublish()
            ->whereNotIn('status', [Product::STATUS_PUBLISHED, Product::STATUS_ACTIVE])
            ->get();

        foreach ($productsToPublish as $product) {
            try {
                $product->publish();
                $count++;
            } catch (\Exception $e) {
                \Log::error("Failed to publish product {$product->id}: " . $e->getMessage());
            }
        }

        // Process products scheduled for unpublish
        $productsToUnpublish = Product::scheduledForUnpublish()
            ->whereIn('status', [Product::STATUS_PUBLISHED, Product::STATUS_ACTIVE])
            ->get();

        foreach ($productsToUnpublish as $product) {
            try {
                $product->unpublish();
                $count++;
            } catch (\Exception $e) {
                \Log::error("Failed to unpublish product {$product->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Create a version snapshot of the product.
     *
     * @param  Product  $product
     * @param  string|null  $versionName
     * @param  string|null  $versionNotes
     * @return ProductVersion
     */
    public function createProductVersion(Product $product, ?string $versionName = null, ?string $versionNotes = null): ProductVersion
    {
        return DB::transaction(function () use ($product, $versionName, $versionNotes) {
            return $product->createVersion($versionName, $versionNotes);
        });
    }

    /**
     * Restore product to a specific version.
     *
     * @param  Product  $product
     * @param  ProductVersion|int  $version
     * @return Product
     */
    public function restoreProductVersion(Product $product, $version): Product
    {
        if ($product->is_locked) {
            throw ValidationException::withMessages([
                'product' => ['Cannot restore locked product. Reason: ' . ($product->lock_reason ?? 'No reason provided')]
            ]);
        }

        return DB::transaction(function () use ($product, $version) {
            return $product->restoreVersion($version);
        });
    }

    /**
     * Publish product with optional scheduling.
     *
     * @param  Product  $product
     * @param  \DateTimeInterface|string|null  $publishAt
     * @return Product
     */
    public function publishProduct(Product $product, $publishAt = null): Product
    {
        $publishDateTime = $publishAt ? (is_string($publishAt) ? \Carbon\Carbon::parse($publishAt) : $publishAt) : null;
        
        if ($publishDateTime && $publishDateTime->isFuture()) {
            // Schedule for future publish
            return $product->schedulePublish($publishDateTime);
        }

        // Publish immediately
        return $product->publish($publishDateTime);
    }

    /**
     * Unpublish product with optional scheduling.
     *
     * @param  Product  $product
     * @param  \DateTimeInterface|string|null  $unpublishAt
     * @return Product
     */
    public function unpublishProduct(Product $product, $unpublishAt = null): Product
    {
        $unpublishDateTime = $unpublishAt ? (is_string($unpublishAt) ? \Carbon\Carbon::parse($unpublishAt) : $unpublishAt) : null;
        
        if ($unpublishDateTime && $unpublishDateTime->isFuture()) {
            // Schedule for future unpublish
            return $product->scheduleUnpublish($unpublishDateTime);
        }

        // Unpublish immediately
        return $product->unpublish();
    }

    /**
     * Lock product with reason.
     *
     * @param  Product  $product
     * @param  string|null  $reason
     * @param  \App\Models\User|null  $user
     * @return Product
     */
    public function lockProduct(Product $product, ?string $reason = null, ?\App\Models\User $user = null): Product
    {
        return DB::transaction(function () use ($product, $reason, $user) {
            return $product->lock($reason, $user);
        });
    }

    /**
     * Unlock product.
     *
     * @param  Product  $product
     * @return Product
     */
    public function unlockProduct(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            return $product->unlock();
        });
    }

    /**
     * Check if product can be edited.
     *
     * @param  Product  $product
     * @return bool
     */
    public function canEdit(Product $product): bool
    {
        return !$product->is_locked;
    }

    /**
     * Get edit lock reason.
     *
     * @param  Product  $product
     * @return string|null
     */
    public function getLockReason(Product $product): ?string
    {
        return $product->lock_reason;
    }
}
