<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a user signs up (with referral attribution).
 */
class UserSignedUp
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $referralCode = null,
        public ?int $attributionId = null
    ) {}
}


