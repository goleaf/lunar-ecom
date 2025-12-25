<?php

namespace App\Services;

use App\Models\MapPrice;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * MAP Enforcement Service.
 * 
 * Provides methods for managing and enforcing Minimum Advertised Prices.
 */
class MAPEnforcementService
{
    /**
     * Get MAP price for a variant.
     */
    public function getMAPPrice(
        ProductVariant $variant,
        int $currencyId,
        ?int $channelId = null
    ): ?MapPrice {
        return MapPrice::where('product_variant_id', $variant->id)
            ->where('currency_id', $currencyId)
            ->where(function($query) use ($channelId) {
                $query->whereNull('channel_id')
                    ->orWhere('channel_id', $channelId);
            })
            ->active()
            ->orderBy('min_price', 'desc') // Highest MAP takes precedence
            ->first();
    }

    /**
     * Check if price violates MAP.
     */
    public function violatesMAP(int $price, MapPrice $mapPrice): bool
    {
        return $price < $mapPrice->min_price;
    }

    /**
     * Enforce MAP on a cart line.
     */
    public function enforceOnCartLine(CartLine $line, string $level = 'strict'): array
    {
        $purchasable = $line->purchasable;
        
        if (!$purchasable instanceof ProductVariant) {
            return ['enforced' => false];
        }
        
        $cart = $line->cart;
        $mapPrice = $this->getMAPPrice(
            $purchasable,
            $cart->currency_id,
            $cart->channel_id
        );
        
        if (!$mapPrice) {
            return ['enforced' => false];
        }
        
        $currentPrice = $line->final_unit_price ?? $purchasable->price ?? 0;
        
        if ($this->violatesMAP($currentPrice, $mapPrice)) {
            if ($mapPrice->enforcement_level === 'strict' || $level === 'strict') {
                $line->update(['final_unit_price' => $mapPrice->min_price]);
                
                return [
                    'enforced' => true,
                    'original_price' => $currentPrice,
                    'map_price' => $mapPrice->min_price,
                    'level' => 'strict',
                ];
            }
            
            return [
                'enforced' => false,
                'violation' => true,
                'original_price' => $currentPrice,
                'map_price' => $mapPrice->min_price,
                'level' => 'warning',
            ];
        }
        
        return ['enforced' => false];
    }

    /**
     * Create or update MAP price.
     */
    public function setMAPPrice(
        ProductVariant $variant,
        int $currencyId,
        int $minPrice,
        ?int $channelId = null,
        string $enforcementLevel = 'strict',
        ?\DateTime $validFrom = null,
        ?\DateTime $validTo = null
    ): MapPrice {
        return MapPrice::updateOrCreate(
            [
                'product_variant_id' => $variant->id,
                'currency_id' => $currencyId,
                'channel_id' => $channelId,
            ],
            [
                'min_price' => $minPrice,
                'enforcement_level' => $enforcementLevel,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
            ]
        );
    }
}

