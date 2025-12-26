<?php

namespace App\Helpers;

use App\Models\Product;
use App\Services\SizeGuideService;

class SizeGuideHelper
{
    /**
     * Get size guide for a product.
     *
     * @param  Product  $product
     * @param  string|null  $region
     * @return \App\Models\SizeGuide|null
     */
    public static function getSizeGuide(Product $product, ?string $region = null)
    {
        $service = app(SizeGuideService::class);
        return $service->getSizeGuide($product, $region);
    }

    /**
     * Get fit statistics text.
     *
     * @param  Product  $product
     * @param  string|null  $size
     * @return string
     */
    public static function getFitStatisticsText(Product $product, ?string $size = null): string
    {
        $service = app(SizeGuideService::class);
        $stats = $service->getFitStatistics($product, $size);

        if ($stats['total_reviews'] === 0) {
            return 'No fit reviews yet';
        }

        $trueToSize = $stats['true_to_size_percentage'];
        
        if ($trueToSize >= 80) {
            return "{$trueToSize}% of customers say this runs true to size";
        } elseif ($trueToSize >= 60) {
            return "{$trueToSize}% of customers say this runs true to size";
        } else {
            return "Based on {$stats['total_reviews']} customer reviews";
        }
    }

    /**
     * Format measurement value.
     *
     * @param  float  $value
     * @param  string  $unit
     * @return string
     */
    public static function formatMeasurement(float $value, string $unit): string
    {
        if ($unit === 'inches') {
            return number_format($value, 1) . '"';
        }

        return number_format($value, 1) . ' cm';
    }

    /**
     * Convert measurement between units.
     *
     * @param  float  $value
     * @param  string  $fromUnit
     * @param  string  $toUnit
     * @return float
     */
    public static function convertMeasurement(float $value, string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) {
            return $value;
        }

        if ($fromUnit === 'cm' && $toUnit === 'inches') {
            return $value / 2.54;
        }

        if ($fromUnit === 'inches' && $toUnit === 'cm') {
            return $value * 2.54;
        }

        return $value;
    }
}


