<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\AbandonedCart;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Illuminate\Support\Facades\Auth;

/**
 * Service for tracking and managing abandoned carts.
 */
class AbandonedCartService
{
    /**
     * Track abandoned cart.
     *
     * @param  Cart  $cart
     * @return void
     */
    public function trackAbandonedCart(Cart $cart): void
    {
        // Only track if cart has items and hasn't been converted
        if ($cart->lines->isEmpty()) {
            return;
        }
        
        foreach ($cart->lines as $line) {
            $purchasable = $line->purchasable;
            
            if (!$purchasable instanceof ProductVariant) {
                continue;
            }
            
            $product = $purchasable->product;
            
            // Check if already tracked
            $existing = AbandonedCart::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('variant_id', $purchasable->id)
                ->where('status', 'abandoned')
                ->first();
            
            if ($existing) {
                continue;
            }
            
            AbandonedCart::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $purchasable->id,
                'user_id' => $cart->user_id,
                'session_id' => $cart->session_id,
                'email' => $cart->user?->email,
                'quantity' => $line->quantity,
                'price' => $line->price->value ?? 0,
                'total' => $line->sub_total->value ?? 0,
                'abandoned_at' => now(),
                'status' => 'abandoned',
            ]);
        }
    }

    /**
     * Mark cart as recovered.
     *
     * @param  Cart  $cart
     * @return void
     */
    public function markAsRecovered(Cart $cart): void
    {
        AbandonedCart::where('cart_id', $cart->id)
            ->where('status', 'abandoned')
            ->update([
                'status' => 'recovered',
                'recovered_at' => now(),
            ]);
    }

    /**
     * Mark cart as converted.
     *
     * @param  Cart  $cart
     * @return void
     */
    public function markAsConverted(Cart $cart): void
    {
        AbandonedCart::where('cart_id', $cart->id)
            ->whereIn('status', ['abandoned', 'recovered'])
            ->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);
    }

    /**
     * Get abandoned cart rate for product.
     *
     * @param  Product  $product
     * @param  \Carbon\Carbon|null  $startDate
     * @param  \Carbon\Carbon|null  $endDate
     * @return float
     */
    public function getAbandonedCartRate(
        Product $product,
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): float {
        $query = AbandonedCart::where('product_id', $product->id)
            ->where('status', 'abandoned');
        
        if ($startDate) {
            $query->where('abandoned_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('abandoned_at', '<=', $endDate);
        }
        
        $abandoned = $query->count();
        
        // Get total cart additions (would need cart tracking)
        $totalAdditions = $abandoned; // Placeholder
        
        return $totalAdditions > 0 ? ($abandoned / $totalAdditions) : 0;
    }

    /**
     * Get abandoned carts for product.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbandonedCartsForProduct(Product $product, int $limit = 50)
    {
        return AbandonedCart::where('product_id', $product->id)
            ->where('status', 'abandoned')
            ->orderByDesc('abandoned_at')
            ->limit($limit)
            ->with(['variant', 'user'])
            ->get();
    }

    /**
     * Send recovery email for abandoned cart.
     *
     * @param  AbandonedCart  $abandonedCart
     * @return void
     */
    public function sendRecoveryEmail(AbandonedCart $abandonedCart): void
    {
        if (!$abandonedCart->email) {
            return;
        }
        
        // Increment recovery email count
        $abandonedCart->increment('recovery_emails_sent');
        $abandonedCart->update(['last_recovery_email_at' => now()]);
        
        // Send email notification
        // This would integrate with your notification system
        // \Notification::send($abandonedCart->user, new AbandonedCartRecoveryNotification($abandonedCart));
    }
}

