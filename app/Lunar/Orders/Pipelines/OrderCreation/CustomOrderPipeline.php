<?php

namespace App\Lunar\Orders\Pipelines\OrderCreation;

use Closure;
use Lunar\Models\Order;

/**
 * Example custom order pipeline.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/orders#pipelines
 * 
 * Pipelines are executed during order creation and allow you to modify
 * the order before it's finalized.
 */
class CustomOrderPipeline
{
    /**
     * Handle the order creation pipeline.
     *
     * @param Order $order
     * @param Closure $next
     * @return Order
     */
    public function handle(Order $order, Closure $next): Order
    {
        // Example: Add custom logic here
        // You can modify the order, add custom metadata, send notifications, etc.
        
        // Do something to the order...
        // e.g., add custom metadata
        // e.g., send notifications
        // e.g., create related records (tickets, fulfillment records, etc.)
        // e.g., apply custom business logic
        
        // Always call $next($order) to continue the pipeline
        return $next($order);
    }
}


