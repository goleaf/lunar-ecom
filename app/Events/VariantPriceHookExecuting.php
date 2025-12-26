<?php

namespace App\Events;

use App\Models\ProductVariant;
use App\Models\VariantPriceHook;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a variant price hook is executing.
 * 
 * Listeners can set the price property to override the hook calculation.
 */
class VariantPriceHookExecuting
{
    use Dispatchable, SerializesModels;

    public ?int $price = null;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public VariantPriceHook $hook,
        public ProductVariant $variant,
        public Currency $currency,
        public int $quantity,
        public ?Channel $channel,
        public ?CustomerGroup $customerGroup
    ) {
    }
}


