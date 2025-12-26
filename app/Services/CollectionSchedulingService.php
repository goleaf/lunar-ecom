<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;
use Carbon\Carbon;

/**
 * Service for managing scheduled collections and auto-publishing products.
 */
class CollectionSchedulingService
{
    /**
     * Process all collections scheduled for publish.
     *
     * @return SupportCollection
     */
    public function processScheduledPublishes(): SupportCollection
    {
        $collections = Collection::scheduledForPublish()->get();
        $processed = collect();

        foreach ($collections as $collection) {
            try {
                $this->publishCollection($collection);
                $processed->push([
                    'collection' => $collection,
                    'action' => 'published',
                    'success' => true,
                ]);
            } catch (\Exception $e) {
                $processed->push([
                    'collection' => $collection,
                    'action' => 'published',
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Process all collections scheduled for unpublish.
     *
     * @return SupportCollection
     */
    public function processScheduledUnpublishes(): SupportCollection
    {
        $collections = Collection::scheduledForUnpublish()->get();
        $processed = collect();

        foreach ($collections as $collection) {
            try {
                $this->unpublishCollection($collection);
                $processed->push([
                    'collection' => $collection,
                    'action' => 'unpublished',
                    'success' => true,
                ]);
            } catch (\Exception $e) {
                $processed->push([
                    'collection' => $collection,
                    'action' => 'unpublished',
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Process all scheduled collections (both publish and unpublish).
     *
     * @return array
     */
    public function processAllScheduled(): array
    {
        return [
            'published' => $this->processScheduledPublishes(),
            'unpublished' => $this->processScheduledUnpublishes(),
        ];
    }

    /**
     * Publish a collection and optionally its products.
     *
     * @param  Collection  $collection
     * @return void
     */
    public function publishCollection(Collection $collection): void
    {
        // Clear scheduled publish date
        $collection->clearScheduledPublish();

        // If auto_publish_products is enabled, publish all products in collection
        if ($collection->auto_publish_products) {
            $this->publishCollectionProducts($collection);
        }
    }

    /**
     * Unpublish a collection and optionally its products.
     *
     * @param  Collection  $collection
     * @return void
     */
    public function unpublishCollection(Collection $collection): void
    {
        // Clear scheduled unpublish date
        $collection->clearScheduledUnpublish();

        // If auto_publish_products is enabled, unpublish all products in collection
        if ($collection->auto_publish_products) {
            $this->unpublishCollectionProducts($collection);
        }
    }

    /**
     * Publish all products in a collection.
     *
     * @param  Collection  $collection
     * @return void
     */
    protected function publishCollectionProducts(Collection $collection): void
    {
        $products = $collection->products()->get();

        foreach ($products as $product) {
            // Only publish if product is not already published
            if (!$product->isPublished()) {
                $product->status = Product::STATUS_PUBLISHED;
                $product->published_at = now();
                $product->save();
            }
        }
    }

    /**
     * Unpublish all products in a collection.
     *
     * @param  Collection  $collection
     * @return void
     */
    protected function unpublishCollectionProducts(Collection $collection): void
    {
        $products = $collection->products()->get();

        foreach ($products as $product) {
            // Only unpublish if product is currently published
            if ($product->isPublished()) {
                $product->status = Product::STATUS_DRAFT;
                $product->save();
            }
        }
    }

    /**
     * Schedule a collection for publish at a specific date/time.
     *
     * @param  Collection  $collection
     * @param  Carbon|string  $publishAt
     * @param  bool  $autoPublishProducts
     * @return void
     */
    public function schedulePublish(Collection $collection, $publishAt, bool $autoPublishProducts = true): void
    {
        $collection->scheduled_publish_at = is_string($publishAt) ? Carbon::parse($publishAt) : $publishAt;
        $collection->auto_publish_products = $autoPublishProducts;
        $collection->save();
    }

    /**
     * Schedule a collection for unpublish at a specific date/time.
     *
     * @param  Collection  $collection
     * @param  Carbon|string  $unpublishAt
     * @param  bool  $autoPublishProducts
     * @return void
     */
    public function scheduleUnpublish(Collection $collection, $unpublishAt, bool $autoPublishProducts = true): void
    {
        $collection->scheduled_unpublish_at = is_string($unpublishAt) ? Carbon::parse($unpublishAt) : $unpublishAt;
        $collection->auto_publish_products = $autoPublishProducts;
        $collection->save();
    }

    /**
     * Validate scheduling dates to prevent conflicts.
     *
     * @param  Collection  $collection
     * @param  Carbon|null  $publishAt
     * @param  Carbon|null  $unpublishAt
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateScheduling(Collection $collection, ?Carbon $publishAt = null, ?Carbon $unpublishAt = null): bool
    {
        $publishAt = $publishAt ?? $collection->scheduled_publish_at;
        $unpublishAt = $unpublishAt ?? $collection->scheduled_unpublish_at;

        // If both dates are set, unpublish must be after publish
        if ($publishAt && $unpublishAt && $unpublishAt->lte($publishAt)) {
            throw new \InvalidArgumentException('Unpublish date must be after publish date.');
        }

        // Publish date must be in the future
        if ($publishAt && $publishAt->isPast()) {
            throw new \InvalidArgumentException('Publish date must be in the future.');
        }

        // Unpublish date must be in the future
        if ($unpublishAt && $unpublishAt->isPast()) {
            throw new \InvalidArgumentException('Unpublish date must be in the future.');
        }

        return true;
    }

    /**
     * Get all collections scheduled for a date range.
     *
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return SupportCollection
     */
    public function getScheduledCollections(?Carbon $startDate = null, ?Carbon $endDate = null): SupportCollection
    {
        $query = Collection::scheduled();

        if ($startDate) {
            $query->where(function ($q) use ($startDate) {
                $q->where('scheduled_publish_at', '>=', $startDate)
                  ->orWhere('scheduled_unpublish_at', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->where(function ($q) use ($endDate) {
                $q->where('scheduled_publish_at', '<=', $endDate)
                  ->orWhere('scheduled_unpublish_at', '<=', $endDate);
            });
        }

        return $query->get();
    }
}

