<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Addresses\AddressHelper;
use App\Lunar\Customers\CustomerHelper;
use Illuminate\Support\Facades\Gate;
use Lunar\Models\Address;
use Livewire\Component;

class AddressesIndex extends Component
{
    public function render()
    {
        Gate::authorize('viewAny', Address::class);

        $user = auth('web')->user();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);

        $addresses = AddressHelper::getForCustomer($customer->id)->load('country');
        $defaultShipping = AddressHelper::getDefaultShipping($customer->id);
        $defaultBilling = AddressHelper::getDefaultBilling($customer->id);

        return view('frontend.addresses.index', compact('addresses', 'defaultShipping', 'defaultBilling'));
    }
}


