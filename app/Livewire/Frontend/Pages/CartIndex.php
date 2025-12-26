<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\CartController;
use Livewire\Component;

class CartIndex extends Component
{
    public function render()
    {
        return app(CartController::class)->index();
    }
}


