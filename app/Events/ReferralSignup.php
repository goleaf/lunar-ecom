<?php

namespace App\Events;

use App\Models\ReferralCode;
use App\Models\User;
use Lunar\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralSignup
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReferralCode $referralCode,
        public User $referee,
        public ?Customer $refereeCustomer = null
    ) {}
}

