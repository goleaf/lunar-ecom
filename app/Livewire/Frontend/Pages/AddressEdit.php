<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\AddressController;
use Livewire\Component;
use Lunar\Models\Address;

class AddressEdit extends Component
{
    public Address $address;

    public function mount(Address $address): void
    {
        $this->address = $address;
    }

    public function render()
    {
        return app(AddressController::class)->edit($this->address);
    }
}


