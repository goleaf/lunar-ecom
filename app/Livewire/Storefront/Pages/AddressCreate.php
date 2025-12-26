<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\AddressController;
use Livewire\Component;

class AddressCreate extends Component
{
    public function render()
    {
        return app(AddressController::class)->create();
    }
}


