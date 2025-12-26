<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\CartController;
use Livewire\Component;

class CartIndex extends Component
{
    public function render()
    {
        return app(CartController::class)->index();
    }
}


