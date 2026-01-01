<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Addresses\AddressHelper;
use App\Lunar\Customers\CustomerHelper;
use Livewire\Component;

class AddressCreate extends Component
{
    public function render()
    {
        $user = auth('web')->user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $countries = AddressHelper::getCountries();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);

        return view('frontend.addresses.create', compact('countries', 'customer'));
    }
}


