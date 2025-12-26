<?php

namespace App\Events;

use App\Models\ReferralCode;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralCodeClicked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReferralCode $referralCode,
        public ?string $sessionId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {}
}


