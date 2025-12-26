<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\CheckoutController;
use Livewire\Component;
use Lunar\Models\Order;

class CheckoutConfirmation extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function render()
    {
        return app(CheckoutController::class)->confirmation($this->order);
    }
}


