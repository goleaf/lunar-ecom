<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Cart as LunarCart;
use Lunar\Models\Channel;

class Cart extends LunarCart
{
    /**
     * Channel relationship for carts.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
