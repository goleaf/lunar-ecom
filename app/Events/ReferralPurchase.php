<?php

namespace App\Events;

use App\Models\ReferralCode;
use Lunar\Models\Order;
use App\Models\User;
use Lunar\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralPurchase
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReferralCode $referralCode,
        public Order $order,
        public User $referee,
        public ?Customer $refereeCustomer = null,
        public bool $isFirstPurchase = false
    ) {}
}


