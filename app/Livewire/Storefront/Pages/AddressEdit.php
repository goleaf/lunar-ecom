<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\AddressController;
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


