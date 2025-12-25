<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PriceElasticity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for calculating price elasticity.
 */
class PriceElasticityService
{
    /**
     * Calculate price elasticity for a price change.
     *
     * @param  ProductVariant  $variant
     * @param  int  $oldPrice
     * @param  int  $newPrice
     * @param  Carbon  $priceChangedAt
     * @param  int  $daysBefore
     * @param  int  $daysAfter
     * @return PriceElasticity
     */
    public function calculateElasticity(
        ProductVariant $variant,
        int $oldPrice,
        int $newPrice,
        Carbon $priceChangedAt,
        int $daysBefore = 30,
        int $daysAfter = 30
    ): PriceElasticity {
        $startDate = $priceChangedAt->copy()->subDays($daysBefore);
        $endDate = $priceChangedAt->copy()->addDays($daysAfter);
        
        // Get sales before price change
        $salesBefore = \Lunar\Models\OrderLine::where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->whereHas('order', function ($q) use ($startDate, $priceChangedAt) {
                $q->whereBetween('placed_at', [$startDate, $priceChangedAt])
                  ->whereIn('status', ['placed', 'completed']);
            })
            ->sum('quantity');
        
        // Get sales after price change
        $salesAfter = \Lunar\Models\OrderLine::where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->whereHas('order', function ($q) use ($priceChangedAt, $endDate) {
                $q->whereBetween('placed_at', [$priceChangedAt, $endDate])
                  ->whereIn('status', ['placed', 'completed']);
            })
            ->sum('quantity');
        
        // Get revenue before/after
        $revenueBefore = \Lunar\Models\OrderLine::where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->whereHas('order', function ($q) use ($startDate, $priceChangedAt) {
                $q->whereBetween('placed_at', [$startDate, $priceChangedAt])
                  ->whereIn('status', ['placed', 'completed']);
            })
            ->sum(DB::raw('(sub_total->>"$.value")'));
        
        $revenueAfter = \Lunar\Models\OrderLine::where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->whereHas('order', function ($q) use ($priceChangedAt, $endDate) {
                $q->whereBetween('placed_at', [$priceChangedAt, $endDate])
                  ->whereIn('status', ['placed', 'completed']);
            })
            ->sum(DB::raw('(sub_total->>"$.value")'));
        
        // Calculate percentages
        $priceChangePercent = $oldPrice > 0 
            ? (($newPrice - $oldPrice) / $oldPrice) * 100 
            : 0;
        
        $salesChangePercent = $salesBefore > 0 
            ? (($salesAfter - $salesBefore) / $salesBefore) * 100 
            : 0;
        
        $revenueChangePercent = $revenueBefore > 0 
            ? (($revenueAfter - $revenueBefore) / $revenueBefore) * 100 
            : 0;
        
        // Calculate elasticity
        // Elasticity = (% change in quantity) / (% change in price)
        $priceElasticity = $priceChangePercent != 0 
            ? ($salesChangePercent / $priceChangePercent) 
            : null;
        
        return PriceElasticity::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'price_change_percent' => $priceChangePercent,
            'price_changed_at' => $priceChangedAt,
            'sales_before' => $salesBefore,
            'sales_after' => $salesAfter,
            'sales_change_percent' => $salesChangePercent,
            'revenue_before' => $revenueBefore,
            'revenue_after' => $revenueAfter,
            'revenue_change_percent' => $revenueChangePercent,
            'price_elasticity' => $priceElasticity,
            'days_before' => $daysBefore,
            'days_after' => $daysAfter,
            'analysis_date' => now(),
        ]);
    }

    /**
     * Get price elasticity for product.
     *
     * @param  Product  $product
     * @return array
     */
    public function getElasticityForProduct(Product $product): array
    {
        $elasticities = PriceElasticity::where('product_id', $product->id)
            ->whereNotNull('price_elasticity')
            ->get();
        
        if ($elasticities->isEmpty()) {
            return [
                'average_elasticity' => null,
                'is_elastic' => null,
                'recommendation' => null,
            ];
        }
        
        $averageElasticity = $elasticities->avg('price_elasticity');
        
        // < -1: Elastic (demand sensitive to price)
        // -1 to 0: Inelastic (demand less sensitive)
        // > 0: Giffen good (demand increases with price)
        $isElastic = $averageElasticity < -1;
        
        $recommendation = match(true) {
            $averageElasticity < -1 => 'Elastic - Price changes significantly affect demand',
            $averageElasticity >= -1 && $averageElasticity < 0 => 'Inelastic - Price changes have moderate effect',
            $averageElasticity >= 0 => 'Giffen good - Demand increases with price',
            default => 'Insufficient data',
        };
        
        return [
            'average_elasticity' => $averageElasticity,
            'is_elastic' => $isElastic,
            'recommendation' => $recommendation,
            'data_points' => $elasticities->count(),
        ];
    }
}

