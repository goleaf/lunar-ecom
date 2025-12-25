<?php

namespace App\Lunar\Cart\Pipelines\Cart;

use Closure;
use Lunar\Models\Cart;

/**
 * Example custom cart pipeline.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/carts#pipelines
 * 
 * Pipelines are executed during cart calculation and allow you to modify
 * the cart before totals are calculated.
 */
class CustomCartPipeline
{
    /**
     * Called during cart calculation pipeline.
     *
     * @param Cart $cart
     * @param Closure $next
     * @return Cart
     */
    public function handle(Cart $cart, Closure $next): Cart
    {
        // Example: Add custom logic here
        // You can modify the cart, add custom metadata, apply custom discounts, etc.
        
        // Do something to the cart...
        // e.g., apply a custom discount based on cart total
        // e.g., add custom metadata
        // e.g., modify cart lines
        
        // Always call $next($cart) to continue the pipeline
        return $next($cart);
    }
}


