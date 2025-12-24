<?php

namespace App\Contracts;

use Lunar\Models\Cart;
use App\Models\User;

interface CartSessionInterface
{
    /**
     * Get current cart from session
     */
    public function current(): ?Cart;

    /**
     * Create a new cart
     */
    public function create(): Cart;

    /**
     * Associate cart with user
     */
    public function associate(User $user): void;
}