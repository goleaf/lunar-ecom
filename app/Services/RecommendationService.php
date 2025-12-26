<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPurchaseAssociation;
use App\Models\ProductView;
use App\Models\RecommendationClick;
use App\Models\RecommendationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for product recommendations using multiple algorithms.
 */
class RecommendationService
{
    /**
     * Get related products based on category, tags, and attributes.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getRelatedProducts(Product $product, int $limit = 10): Collection
    {
        $cacheKey = "recommendations:related:{$product->id}:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($product, $limit) {
            $products = collect();

            // 1. Same category products (weight: 40%)
            $categoryProducts = $this->getProductsByCategory($product, $limit * 2);
            $products = $products->merge($categoryProducts->map(fn($p) => ['product' => $p, 'score' => 0.4]));

            // 2. Same tags/products (weight: 30%)
            $tagProducts = $this->getProductsByTags($product, $limit * 2);
            $products = $products->merge($tagProducts->map(fn($p) => ['product' => $p, 'score' => 0.3]));

            // 3. Similar attributes (weight: 20%)
            $attributeProducts = $this->getProductsByAttributes($product, $limit * 2);
            $products = $products->merge($attributeProducts->map(fn($p) => ['product' => $p, 'score' => 0.2]));

            // 4. Same brand (weight: 10%)
            if ($product->brand_id) {
                $brandProducts = Product::where('brand_id', $product->brand_id)
                    ->where('id', '!=', $product->id)
                    ->published()
                    ->limit($limit)
                    ->get();
                $products = $products->merge($brandProducts->map(fn($p) => ['product' => $p, 'score' => 0.1]));
            }

            // Aggregate scores and return top products
            $aggregated = $products->groupBy('product.id')
                ->map(function ($items) {
                    $product = $items->first()['product'];
                    $score = $items->sum('score');
                    return ['product' => $product, 'score' => $score];
                })
                ->sortByDesc('score')
                ->take($limit)
                ->pluck('product');

            return $aggregated;
        });
    }

    /**
     * Get frequently bought together products using association rules.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 10): Collection
    {
        $cacheKey = "recommendations:frequently_bought:{$product->id}:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(12), function () use ($product, $limit) {
            // Get from purchase associations
            $associations = ProductPurchaseAssociation::where('product_id', $product->id)
                ->highConfidence(0.2) // Minimum confidence threshold
                ->topAssociations($limit * 2)
                ->with('associatedProduct')
                ->get();

            $products = $associations->map(function ($association) {
                return $association->associatedProduct;
            })->filter();

            // If not enough associations, fall back to order-based analysis
            if ($products->count() < $limit) {
                $orderProducts = $this->analyzeOrderHistory($product, $limit - $products->count());
                $products = $products->merge($orderProducts);
            }

            return $products->take($limit);
        });
    }

    /**
     * Get cross-selling products (complementary products).
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getCrossSellingProducts(Product $product, int $limit = 10): Collection
    {
        $cacheKey = "recommendations:cross_sell:{$product->id}:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($product, $limit) {
            // Cross-sell logic: products that complement but aren't directly related
            // Example: if product is a camera, recommend memory cards, cases, etc.
            
            $products = collect();

            // 1. Products from complementary categories
            $complementaryCategories = $this->getComplementaryCategories($product);
            foreach ($complementaryCategories as $category) {
                $categoryProducts = Product::whereHas('categories', function ($q) use ($category) {
                    $q->where('categories.id', $category->id);
                })
                ->where('id', '!=', $product->id)
                ->published()
                ->limit($limit)
                ->get();
                
                $products = $products->merge($categoryProducts);
            }

            // 2. Products with complementary attributes
            $complementaryAttributes = $this->getComplementaryAttributes($product);
            if ($complementaryAttributes->isNotEmpty()) {
                $attributeProducts = Product::whereHas('attributeValues', function ($q) use ($complementaryAttributes) {
                    $q->whereIn('attribute_id', $complementaryAttributes->pluck('id'));
                })
                ->where('id', '!=', $product->id)
                ->published()
                ->limit($limit)
                ->get();
                
                $products = $products->merge($attributeProducts);
            }

            return $products->unique('id')->take($limit);
        });
    }

    /**
     * Get personalized recommendations based on user behavior.
     *
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @param  int  $limit
     * @return Collection
     */
    public function getPersonalizedRecommendations(?int $userId = null, ?string $sessionId = null, int $limit = 10): Collection
    {
        if (!$userId && !$sessionId) {
            return collect();
        }

        $cacheKey = "recommendations:personalized:" . ($userId ?? $sessionId) . ":{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(2), function () use ($userId, $sessionId, $limit) {
            $products = collect();

            // 1. Based on browsing history (40% weight)
            $viewedProducts = $this->getProductsFromViews($userId, $sessionId, $limit * 2);
            $products = $products->merge($viewedProducts->map(fn($p) => ['product' => $p, 'score' => 0.4]));

            // 2. Based on wishlist (30% weight)
            if ($userId) {
                $wishlistProducts = $this->getProductsFromWishlist($userId, $limit);
                $products = $products->merge($wishlistProducts->map(fn($p) => ['product' => $p, 'score' => 0.3]));
            }

            // 3. Based on past purchases (30% weight)
            if ($userId) {
                $purchaseProducts = $this->getProductsFromPurchases($userId, $limit);
                $products = $products->merge($purchaseProducts->map(fn($p) => ['product' => $p, 'score' => 0.3]));
            }

            // Aggregate and return top products
            $aggregated = $products->groupBy('product.id')
                ->map(function ($items) {
                    $product = $items->first()['product'];
                    $score = $items->sum('score');
                    return ['product' => $product, 'score' => $score];
                })
                ->sortByDesc('score')
                ->take($limit)
                ->pluck('product');

            return $aggregated;
        });
    }

    /**
     * Get recommendations using collaborative filtering.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getCollaborativeFilteringRecommendations(Product $product, int $limit = 10): Collection
    {
        $cacheKey = "recommendations:collaborative:{$product->id}:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($product, $limit) {
            // Find users who viewed/bought this product
            $userIds = ProductView::where('product_id', $product->id)
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');

            if ($userIds->isEmpty()) {
                return collect();
            }

            // Find products those users also viewed/bought
            $relatedProductIds = ProductView::whereIn('user_id', $userIds)
                ->where('product_id', '!=', $product->id)
                ->select('product_id', DB::raw('COUNT(*) as view_count'))
                ->groupBy('product_id')
                ->orderByDesc('view_count')
                ->limit($limit * 2)
                ->pluck('product_id');

            // Also check purchases
            $purchasedProductIds = DB::table(config('lunar.database.table_prefix') . 'order_lines')
                ->join(config('lunar.database.table_prefix') . 'orders', 'order_lines.order_id', '=', 'orders.id')
                ->whereIn('orders.user_id', $userIds)
                ->where('order_lines.purchasable_type', 'like', '%ProductVariant%')
                ->where('order_lines.product_id', '!=', $product->id)
                ->select('order_lines.product_id', DB::raw('COUNT(*) as purchase_count'))
                ->groupBy('order_lines.product_id')
                ->orderByDesc('purchase_count')
                ->limit($limit)
                ->pluck('product_id');

            $allProductIds = $relatedProductIds->merge($purchasedProductIds)->unique();

            return Product::whereIn('id', $allProductIds)
                ->published()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get recommendations for a product (combines multiple algorithms).
     *
     * @param  Product  $product
     * @param  string  $algorithm
     * @param  string  $location
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @param  int  $limit
     * @param  string|null  $abTestVariant
     * @return Collection
     */
    public function getRecommendations(
        Product $product,
        string $algorithm = 'hybrid',
        string $location = 'product_page',
        ?int $userId = null,
        ?string $sessionId = null,
        int $limit = 10,
        ?string $abTestVariant = null
    ): Collection {
        // A/B testing: assign variant if not provided
        if (!$abTestVariant) {
            $abTestVariant = $this->assignABTestVariant($userId, $sessionId, $location);
        }
        // Check manual rules first (highest priority)
        $manualRules = RecommendationRule::where('source_product_id', $product->id)
            ->active()
            ->orderedByPriority()
            ->with('recommendedProduct')
            ->limit($limit)
            ->get();

        if ($manualRules->isNotEmpty()) {
            $manualProducts = $manualRules->map(function ($rule) use ($location) {
                $rule->incrementDisplay();
                return $rule->recommendedProduct;
            })->filter();

            if ($manualProducts->count() >= $limit) {
                return $manualProducts->take($limit);
            }
        }

        // A/B testing: override algorithm based on variant
        $effectiveAlgorithm = $this->getAlgorithmForABTest($algorithm, $abTestVariant);

        // Get algorithmic recommendations
        $algorithmicProducts = match ($effectiveAlgorithm) {
            'related' => $this->getRelatedProducts($product, $limit),
            'frequently_bought_together' => $this->getFrequentlyBoughtTogether($product, $limit),
            'cross_sell' => $this->getCrossSellingProducts($product, $limit),
            'personalized' => $this->getPersonalizedRecommendations($userId, $sessionId, $limit),
            'collaborative' => $this->getCollaborativeFilteringRecommendations($product, $limit),
            'hybrid' => $this->getHybridRecommendations($product, $userId, $sessionId, $limit),
            default => $this->getHybridRecommendations($product, $userId, $sessionId, $limit),
        };

        // Merge manual and algorithmic, remove duplicates
        $allProducts = $manualRules->pluck('recommendedProduct')
            ->merge($algorithmicProducts)
            ->filter()
            ->unique('id')
            ->take($limit);

        // Store A/B test variant in cache for tracking
        if ($abTestVariant) {
            Cache::put("ab_test:{$userId}:{$sessionId}:{$location}", $abTestVariant, now()->addDays(30));
        }

        return $allProducts;
    }

    /**
     * Assign A/B test variant to user/session.
     *
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @param  string  $location
     * @return string
     */
    protected function assignABTestVariant(?int $userId, ?string $sessionId, string $location): string
    {
        $key = $userId ?? $sessionId ?? 'anonymous';
        $cacheKey = "ab_test:{$key}:{$location}";

        // Check if variant already assigned
        $variant = Cache::get($cacheKey);
        if ($variant) {
            return $variant;
        }

        // Assign variant (50/50 split for A/B testing)
        $variant = (($userId ?? crc32($sessionId ?? 'anonymous')) % 2 === 0) ? 'A' : 'B';
        Cache::put($cacheKey, $variant, now()->addDays(30));

        return $variant;
    }

    /**
     * Get algorithm for A/B test variant.
     *
     * @param  string  $baseAlgorithm
     * @param  string  $variant
     * @return string
     */
    protected function getAlgorithmForABTest(string $baseAlgorithm, string $variant): string
    {
        // A/B test configuration: test different algorithms
        $abTestConfig = [
            'A' => [
                'hybrid' => 'hybrid',
                'related' => 'related',
            ],
            'B' => [
                'hybrid' => 'collaborative', // Test collaborative vs hybrid
                'related' => 'personalized', // Test personalized vs related
            ],
        ];

        return $abTestConfig[$variant][$baseAlgorithm] ?? $baseAlgorithm;
    }

    /**
     * Get hybrid recommendations (combines multiple algorithms).
     *
     * @param  Product  $product
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @param  int  $limit
     * @return Collection
     */
    protected function getHybridRecommendations(
        Product $product,
        ?int $userId = null,
        ?string $sessionId = null,
        int $limit = 10
    ): Collection {
        $products = collect();

        // Combine multiple algorithms with weights
        if ($userId || $sessionId) {
            $personalized = $this->getPersonalizedRecommendations($userId, $sessionId, $limit);
            $products = $products->merge($personalized->map(fn($p) => ['product' => $p, 'score' => 0.3]));
        }

        $related = $this->getRelatedProducts($product, $limit);
        $products = $products->merge($related->map(fn($p) => ['product' => $p, 'score' => 0.3]));

        $frequentlyBought = $this->getFrequentlyBoughtTogether($product, $limit);
        $products = $products->merge($frequentlyBought->map(fn($p) => ['product' => $p, 'score' => 0.2]));

        $collaborative = $this->getCollaborativeFilteringRecommendations($product, $limit);
        $products = $products->merge($collaborative->map(fn($p) => ['product' => $p, 'score' => 0.2]));

        // Aggregate scores
        $aggregated = $products->groupBy('product.id')
            ->map(function ($items) {
                $product = $items->first()['product'];
                $score = $items->sum('score');
                return ['product' => $product, 'score' => $score];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('product');

        return $aggregated;
    }

    /**
     * Track product view.
     *
     * @param  Product  $product
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @return void
     */
    public function trackView(Product $product, ?int $userId = null, ?string $sessionId = null): void
    {
        ProductView::create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'viewed_at' => now(),
        ]);

        // Clear personalized recommendations cache
        if ($userId) {
            Cache::forget("recommendations:personalized:{$userId}:*");
        }
        if ($sessionId) {
            Cache::forget("recommendations:personalized:{$sessionId}:*");
        }
    }

    /**
     * Track recommendation click.
     *
     * @param  Product  $sourceProduct
     * @param  Product  $recommendedProduct
     * @param  string  $type
     * @param  string  $location
     * @param  string|null  $algorithm
     * @param  int|null  $userId
     * @param  string|null  $sessionId
     * @return void
     */
    public function trackClick(
        Product $sourceProduct,
        Product $recommendedProduct,
        string $type,
        string $location,
        ?string $algorithm = null,
        ?int $userId = null,
        ?string $sessionId = null
    ): void {
        RecommendationClick::create([
            'source_product_id' => $sourceProduct->id,
            'recommended_product_id' => $recommendedProduct->id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'recommendation_type' => $type,
            'recommendation_algorithm' => $algorithm,
            'display_location' => $location,
            'clicked_at' => now(),
        ]);

        // Update recommendation rule if exists
        $rule = RecommendationRule::where('source_product_id', $sourceProduct->id)
            ->where('recommended_product_id', $recommendedProduct->id)
            ->first();

        if ($rule) {
            $rule->incrementClick();
        }
    }

    /**
     * Mark recommendation as converted (purchased).
     *
     * @param  int  $clickId
     * @param  int  $orderId
     * @return void
     */
    public function markAsConverted(int $clickId, int $orderId): void
    {
        $click = RecommendationClick::find($clickId);
        if ($click) {
            $click->update([
                'converted' => true,
                'order_id' => $orderId,
            ]);
        }
    }

    /**
     * Analyze order history to find co-purchase patterns.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    protected function analyzeOrderHistory(Product $product, int $limit): Collection
    {
        // Get orders containing this product
        $orderIds = DB::table(config('lunar.database.table_prefix') . 'order_lines')
            ->where('purchasable_type', 'like', '%ProductVariant%')
            ->where('product_id', $product->id)
            ->distinct()
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        // Find products in the same orders
        $coPurchasedProductIds = DB::table(config('lunar.database.table_prefix') . 'order_lines')
            ->whereIn('order_id', $orderIds)
            ->where('purchasable_type', 'like', '%ProductVariant%')
            ->where('product_id', '!=', $product->id)
            ->select('product_id', DB::raw('COUNT(DISTINCT order_id) as co_purchase_count'))
            ->groupBy('product_id')
            ->orderByDesc('co_purchase_count')
            ->limit($limit)
            ->pluck('product_id');

        return Product::whereIn('id', $coPurchasedProductIds)
            ->published()
            ->get();
    }

    /**
     * Get products by category.
     */
    protected function getProductsByCategory(Product $product, int $limit): Collection
    {
        $categoryIds = $product->categories->pluck('id');
        
        if ($categoryIds->isEmpty()) {
            return collect();
        }

        return Product::whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        })
        ->where('id', '!=', $product->id)
        ->published()
        ->limit($limit)
        ->get();
    }

    /**
     * Get products by tags.
     */
    protected function getProductsByTags(Product $product, int $limit): Collection
    {
        // Assuming products have tags relationship
        // Adjust based on your Lunar setup
        return Product::where('id', '!=', $product->id)
            ->published()
            ->limit($limit)
            ->get();
    }

    /**
     * Get products by similar attributes.
     */
    protected function getProductsByAttributes(Product $product, int $limit): Collection
    {
        $attributeIds = $product->attributeValues->pluck('attribute_id')->unique();
        
        if ($attributeIds->isEmpty()) {
            return collect();
        }

        return Product::whereHas('attributeValues', function ($q) use ($attributeIds) {
            $q->whereIn('attribute_id', $attributeIds);
        })
        ->where('id', '!=', $product->id)
        ->published()
        ->limit($limit)
        ->get();
    }

    /**
     * Get complementary categories.
     */
    protected function getComplementaryCategories(Product $product): Collection
    {
        // Define complementary category mappings
        // This is a simplified version - you can expand this
        return collect();
    }

    /**
     * Get complementary attributes.
     */
    protected function getComplementaryAttributes(Product $product): Collection
    {
        // Define complementary attribute mappings
        return collect();
    }

    /**
     * Get products from user views.
     */
    protected function getProductsFromViews(?int $userId, ?string $sessionId, int $limit): Collection
    {
        $views = ProductView::forUserOrSession($userId, $sessionId)
            ->recent(30)
            ->with('product')
            ->get()
            ->pluck('product')
            ->filter()
            ->unique('id');

        // Get related products from viewed products
        $relatedProductIds = collect();
        foreach ($views->take(10) as $viewedProduct) {
            $related = $this->getRelatedProducts($viewedProduct, 5);
            $relatedProductIds = $relatedProductIds->merge($related->pluck('id'));
        }

        return Product::whereIn('id', $relatedProductIds)
            ->published()
            ->limit($limit)
            ->get();
    }

    /**
     * Get products from wishlist.
     */
    protected function getProductsFromWishlist(int $userId, int $limit): Collection
    {
        // Adjust based on your wishlist implementation
        return collect();
    }

    /**
     * Get products from past purchases.
     */
    protected function getProductsFromPurchases(int $userId, int $limit): Collection
    {
        $purchasedProductIds = DB::table(config('lunar.database.table_prefix') . 'order_lines')
            ->join(config('lunar.database.table_prefix') . 'orders', 'order_lines.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('order_lines.purchasable_type', 'like', '%ProductVariant%')
            ->distinct()
            ->pluck('order_lines.product_id');

        // Get related products from purchased products
        $relatedProductIds = collect();
        foreach ($purchasedProductIds->take(10) as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $related = $this->getRelatedProducts($product, 5);
                $relatedProductIds = $relatedProductIds->merge($related->pluck('id'));
            }
        }

        return Product::whereIn('id', $relatedProductIds)
            ->published()
            ->whereNotIn('id', $purchasedProductIds) // Exclude already purchased
            ->limit($limit)
            ->get();
    }

    /**
     * Update purchase associations from orders.
     * Should be run periodically (e.g., daily via cron).
     *
     * @return void
     */
    public function updatePurchaseAssociations(): void
    {
        // Get all orders with multiple products
        $orders = DB::table(config('lunar.database.table_prefix') . 'orders')
            ->join(config('lunar.database.table_prefix') . 'order_lines', 'orders.id', '=', 'order_lines.order_id')
            ->where('order_lines.purchasable_type', 'like', '%ProductVariant%')
            ->select('orders.id', 'order_lines.product_id')
            ->get()
            ->groupBy('id')
            ->filter(fn($lines) => $lines->count() > 1);

        foreach ($orders as $orderLines) {
            $productIds = $orderLines->pluck('product_id')->unique()->filter();
            
            // Create associations for all pairs
            foreach ($productIds as $productId1) {
                foreach ($productIds as $productId2) {
                    if ($productId1 !== $productId2) {
                        $this->incrementAssociation($productId1, $productId2);
                    }
                }
            }
        }

        // Calculate confidence, support, and lift for all associations
        $this->calculateAssociationMetrics();
    }

    /**
     * Increment association count.
     */
    protected function incrementAssociation(int $productId1, int $productId2): void
    {
        ProductPurchaseAssociation::updateOrCreate(
            [
                'product_id' => $productId1,
                'associated_product_id' => $productId2,
            ],
            [
                'co_purchase_count' => DB::raw('co_purchase_count + 1'),
            ]
        );
    }

    /**
     * Calculate association rule metrics (confidence, support, lift).
     */
    protected function calculateAssociationMetrics(): void
    {
        // Get total orders count
        $totalOrders = DB::table(config('lunar.database.table_prefix') . 'orders')->count();

        // Get product purchase counts
        $productCounts = DB::table(config('lunar.database.table_prefix') . 'order_lines')
            ->where('purchasable_type', 'like', '%ProductVariant%')
            ->select('product_id', DB::raw('COUNT(DISTINCT order_id) as purchase_count'))
            ->groupBy('product_id')
            ->pluck('purchase_count', 'product_id');

        // Update all associations
        ProductPurchaseAssociation::chunk(100, function ($associations) use ($totalOrders, $productCounts) {
            foreach ($associations as $association) {
                $productCount = $productCounts[$association->product_id] ?? 0;
                $coPurchaseCount = $association->co_purchase_count;

                // Support: P(A and B)
                $support = $totalOrders > 0 ? $coPurchaseCount / $totalOrders : 0;

                // Confidence: P(B|A) = P(A and B) / P(A)
                $confidence = $productCount > 0 ? $coPurchaseCount / $productCount : 0;

                // Lift: P(B|A) / P(B)
                $associatedProductCount = $productCounts[$association->associated_product_id] ?? 0;
                $lift = ($totalOrders > 0 && $associatedProductCount > 0) 
                    ? ($confidence / ($associatedProductCount / $totalOrders)) 
                    : 0;

                $association->update([
                    'support' => round($support, 4),
                    'confidence' => round($confidence, 4),
                    'lift' => round($lift, 4),
                ]);
            }
        });
    }
}

