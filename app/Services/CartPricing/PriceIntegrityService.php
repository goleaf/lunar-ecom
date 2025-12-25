<?php

namespace App\Services\CartPricing;

use App\Models\MapPrice;
use App\Services\CartPricing\DTOs\CartPricingResult;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;

/**
 * Price Integrity Service - Handles price validation and enforcement.
 * 
 * Features:
 * - Minimum price enforcement
 * - MAP (Minimum Advertised Price) enforcement
 * - Price tamper detection
 * - Price expiration checking
 * - Mismatch detection
 */
class PriceIntegrityService
{
    /**
     * Validate cart prices.
     */
    public function validateCartPrices(Cart $cart): ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        foreach ($cart->lines as $line) {
            // Check minimum price
            if ($line->final_unit_price < 0) {
                $errors[] = "Line {$line->id} has negative price";
                $this->enforceMinimumPrice($line);
            }
            
            // Check MAP
            $mapResult = $this->enforceMAP($line);
            if ($mapResult['violation']) {
                if ($mapResult['level'] === 'strict') {
                    $errors[] = "Line {$line->id} violates MAP: {$mapResult['message']}";
                } else {
                    $warnings[] = "Line {$line->id} MAP warning: {$mapResult['message']}";
                }
            }
            
            // Price mismatch is checked at cart level, not line level
            // Individual line price hashes are stored in price_hash field
        }
        
        // Check price expiration
        if ($this->checkPriceExpiration($cart)) {
            $warnings[] = "Cart prices have expired and need recalculation";
            $cart->update(['requires_reprice' => true]);
        }
        
        // Check price mismatch (cart-level hash verification)
        if ($this->detectPriceMismatch($cart)) {
            $warnings[] = "Cart price hash mismatch - prices may have been tampered with";
        }
        
        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Enforce minimum price (prevents negative/zero prices).
     */
    public function enforceMinimumPrice(CartLine $line): void
    {
        if ($line->final_unit_price < 0) {
            $line->update(['final_unit_price' => 0]);
            Log::warning("Enforced minimum price for cart line {$line->id}");
        }
    }

    /**
     * Enforce MAP (Minimum Advertised Price).
     */
    public function enforceMAP(CartLine $line): array
    {
        $purchasable = $line->purchasable;
        
        if (!$purchasable) {
            return ['violation' => false];
        }
        
        $cart = $line->cart;
        $mapPrice = MapPrice::where('product_variant_id', $purchasable->id)
            ->where('currency_id', $cart->currency_id)
            ->where(function($query) use ($cart) {
                $query->whereNull('channel_id')
                    ->orWhere('channel_id', $cart->channel_id);
            })
            ->where(function($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            })
            ->first();
        
        if (!$mapPrice) {
            return ['violation' => false];
        }
        
        $currentPrice = $line->final_unit_price ?? $line->purchasable->price ?? 0;
        
        if ($currentPrice < $mapPrice->min_price) {
            $violation = true;
            $level = $mapPrice->enforcement_level;
            $message = "Price {$currentPrice} is below MAP of {$mapPrice->min_price}";
            
            if ($level === 'strict') {
                // Block the price - set to MAP
                $line->update(['final_unit_price' => $mapPrice->min_price]);
                Log::error("MAP violation (strict) for cart line {$line->id}: {$message}");
            } else {
                // Warning only
                Log::warning("MAP violation (warning) for cart line {$line->id}: {$message}");
            }
            
            return [
                'violation' => true,
                'level' => $level,
                'message' => $message,
                'map_price' => $mapPrice->min_price,
                'current_price' => $currentPrice,
            ];
        }
        
        return ['violation' => false];
    }

    /**
     * Detect price mismatch (compares stored vs calculated).
     */
    public function detectPriceMismatch(Cart $cart): bool
    {
        // Check if price hash matches
        return !$this->verifyPriceHash($cart);
    }

    /**
     * Check if cart prices have expired.
     */
    public function checkPriceExpiration(Cart $cart): bool
    {
        $expirationHours = config('lunar.cart.pricing.price_expiration_hours', 24);
        
        if (!$cart->last_reprice_at) {
            return true; // Never repriced, needs calculation
        }
        
        $expirationTime = $cart->last_reprice_at->copy()->addHours($expirationHours);
        
        return now()->greaterThan($expirationTime);
    }

    /**
     * Generate price hash for tamper detection.
     */
    public function generatePriceHash(Cart $cart, array $pricingData): string
    {
        $hashData = [
            'cart_id' => $cart->id,
            'currency_id' => $cart->currency_id,
            'channel_id' => $cart->channel_id,
            'subtotal' => $pricingData['cart_subtotal'] ?? 0,
            'discounts' => $pricingData['cart_discount_total'] ?? 0,
            'tax' => $pricingData['tax_total'] ?? 0,
            'shipping' => $pricingData['shipping_total'] ?? 0,
            'grand_total' => $pricingData['grand_total'] ?? 0,
            'pricing_version' => $cart->pricing_version ?? 0,
            'line_count' => $cart->lines->count(),
        ];
        
        return hash('sha256', json_encode($hashData));
    }

    /**
     * Verify price hash.
     */
    public function verifyPriceHash(Cart $cart): bool
    {
        if (!$cart->pricing_snapshot) {
            return false; // No pricing snapshot to verify
        }
        
        $storedHash = $cart->pricing_snapshot['price_hash'] ?? null;
        
        if (!$storedHash) {
            return false; // No hash stored
        }
        
        $calculatedHash = $this->generatePriceHash($cart, $cart->pricing_snapshot);
        
        return hash_equals($storedHash, $calculatedHash);
    }
}

/**
 * Validation result DTO.
 */
class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors,
        public readonly array $warnings,
    ) {}
}

