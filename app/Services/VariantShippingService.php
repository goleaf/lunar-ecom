<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Service for calculating shipping-related properties for variants.
 */
class VariantShippingService
{
    /**
     * Calculate volumetric weight.
     * 
     * Formula: (length × width × height) / divisor
     * Default divisor: 5000 (for cm³ to kg conversion)
     *
     * @param  ProductVariant  $variant
     * @return int|null Volumetric weight in grams
     */
    public function calculateVolumetricWeight(ProductVariant $variant): ?int
    {
        $dimensions = $variant->dimensions;
        
        if (!$dimensions || !isset($dimensions['length']) || !isset($dimensions['width']) || !isset($dimensions['height'])) {
            return null;
        }

        $length = $dimensions['length'];
        $width = $dimensions['width'];
        $height = $dimensions['height'];

        if ($length <= 0 || $width <= 0 || $height <= 0) {
            return null;
        }

        // Convert to cm if needed (assuming dimensions are in cm)
        $volume = $length * $width * $height; // cm³

        // Volumetric weight = volume / divisor
        $divisor = $variant->volumetric_divisor ?? 5000; // Default: 5000 cm³ per kg
        $volumetricWeightKg = $volume / $divisor;

        // Convert to grams
        return (int)round($volumetricWeightKg * 1000);
    }

    /**
     * Get shipping weight (actual weight or volumetric weight, whichever is greater).
     *
     * @param  ProductVariant  $variant
     * @return int Weight in grams
     */
    public function getShippingWeight(ProductVariant $variant): int
    {
        $actualWeight = $variant->weight ?? 0;
        $volumetricWeight = $variant->volumetric_weight ?? $this->calculateVolumetricWeight($variant) ?? 0;

        // Shipping weight is the greater of actual or volumetric weight
        return max($actualWeight, $volumetricWeight);
    }

    /**
     * Get shipping weight in kilograms.
     *
     * @param  ProductVariant  $variant
     * @return float|null
     */
    public function getShippingWeightInKg(ProductVariant $variant): ?float
    {
        $weight = $this->getShippingWeight($variant);
        return $weight > 0 ? round($weight / 1000, 3) : null;
    }

    /**
     * Get shipping weight in pounds.
     *
     * @param  ProductVariant  $variant
     * @return float|null
     */
    public function getShippingWeightInLbs(ProductVariant $variant): ?float
    {
        $kg = $this->getShippingWeightInKg($variant);
        return $kg ? round($kg * 2.20462, 3) : null;
    }

    /**
     * Get volume in cubic centimeters.
     *
     * @param  ProductVariant  $variant
     * @return float|null
     */
    public function getVolume(ProductVariant $variant): ?float
    {
        $dimensions = $variant->dimensions;
        
        if (!$dimensions || !isset($dimensions['length']) || !isset($dimensions['width']) || !isset($dimensions['height'])) {
            return null;
        }

        return $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
    }

    /**
     * Get volume in cubic meters.
     *
     * @param  ProductVariant  $variant
     * @return float|null
     */
    public function getVolumeInCubicMeters(ProductVariant $variant): ?float
    {
        $volumeCm3 = $this->getVolume($variant);
        return $volumeCm3 ? round($volumeCm3 / 1000000, 6) : null;
    }

    /**
     * Get dimensions array.
     *
     * @param  ProductVariant  $variant
     * @return array|null ['length' => float, 'width' => float, 'height' => float]
     */
    public function getDimensions(ProductVariant $variant): ?array
    {
        $dimensions = $variant->dimensions;
        
        if (!$dimensions) {
            return null;
        }

        return [
            'length' => $dimensions['length'] ?? null,
            'width' => $dimensions['width'] ?? null,
            'height' => $dimensions['height'] ?? null,
        ];
    }

    /**
     * Get dimensions in inches.
     *
     * @param  ProductVariant  $variant
     * @return array|null
     */
    public function getDimensionsInInches(ProductVariant $variant): ?array
    {
        $dimensions = $this->getDimensions($variant);
        
        if (!$dimensions) {
            return null;
        }

        return [
            'length' => $dimensions['length'] ? round($dimensions['length'] / 2.54, 2) : null,
            'width' => $dimensions['width'] ? round($dimensions['width'] / 2.54, 2) : null,
            'height' => $dimensions['height'] ? round($dimensions['height'] / 2.54, 2) : null,
        ];
    }

    /**
     * Check if variant requires special handling.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function requiresSpecialHandling(ProductVariant $variant): bool
    {
        return $variant->is_fragile || $variant->is_hazardous;
    }

    /**
     * Get shipping requirements.
     *
     * @param  ProductVariant  $variant
     * @return array
     */
    public function getShippingRequirements(ProductVariant $variant): array
    {
        return [
            'shipping_class' => $variant->shipping_class,
            'is_fragile' => $variant->is_fragile ?? false,
            'is_hazardous' => $variant->is_hazardous ?? false,
            'hazardous_class' => $variant->hazardous_class,
            'requires_special_handling' => $this->requiresSpecialHandling($variant),
            'shipping_weight' => $this->getShippingWeight($variant),
            'shipping_weight_kg' => $this->getShippingWeightInKg($variant),
            'volumetric_weight' => $variant->volumetric_weight ?? $this->calculateVolumetricWeight($variant),
            'dimensions' => $this->getDimensions($variant),
        ];
    }

    /**
     * Get customs information.
     *
     * @param  ProductVariant  $variant
     * @return array
     */
    public function getCustomsInfo(ProductVariant $variant): array
    {
        return [
            'hs_code' => $variant->hs_code ?? $variant->product->hs_code ?? null,
            'origin_country' => $variant->origin_country ?? $variant->product->origin_country ?? null,
            'customs_description' => $variant->customs_description ?? $variant->product->translateAttribute('name'),
            'weight' => $this->getShippingWeight($variant),
            'value' => $variant->getEffectivePrice() ?? 0,
        ];
    }

    /**
     * Get lead time information.
     *
     * @param  ProductVariant  $variant
     * @return array
     */
    public function getLeadTimeInfo(ProductVariant $variant): array
    {
        $leadTimeDays = $variant->lead_time_days ?? $variant->product->lead_time_days ?? 0;
        
        return [
            'lead_time_days' => $leadTimeDays,
            'lead_time_weeks' => round($leadTimeDays / 7, 1),
            'estimated_ship_date' => $leadTimeDays > 0 ? now()->addDays($leadTimeDays) : now(),
            'is_made_to_order' => $leadTimeDays > 0,
        ];
    }

    /**
     * Update volumetric weight for variant.
     *
     * @param  ProductVariant  $variant
     * @return void
     */
    public function updateVolumetricWeight(ProductVariant $variant): void
    {
        $volumetricWeight = $this->calculateVolumetricWeight($variant);
        
        if ($volumetricWeight !== null) {
            $variant->update(['volumetric_weight' => $volumetricWeight]);
        }
    }

    /**
     * Bulk update volumetric weights.
     *
     * @param  Collection  $variants
     * @return int Number of updated variants
     */
    public function bulkUpdateVolumetricWeights(Collection $variants): int
    {
        $updated = 0;

        foreach ($variants as $variant) {
            $volumetricWeight = $this->calculateVolumetricWeight($variant);
            
            if ($volumetricWeight !== null) {
                $variant->update(['volumetric_weight' => $volumetricWeight]);
                $updated++;
            }
        }

        return $updated;
    }
}

