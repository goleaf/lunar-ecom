<?php

namespace App\Livewire\Frontend\Pages;

use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('view', $this->order);

        $order = $this->order;

        return view('frontend.checkout.confirmation', compact('order'));
    }
}


