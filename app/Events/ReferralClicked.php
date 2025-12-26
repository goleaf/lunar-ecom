<?php

namespace App\Events;

use App\Models\ReferralClick;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a referral link is clicked.
 */
class ReferralClicked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReferralClick $click
    ) {}
}


