<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\AddressController;
use Livewire\Component;

class AddressCreate extends Component
{
    public function render()
    {
        return app(AddressController::class)->create();
    }
}


