<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Addresses\AddressHelper;
use Illuminate\Support\Facades\Gate;
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
        $user = auth('web')->user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        Gate::authorize('update', $this->address);

        $address = $this->address;
        $countries = AddressHelper::getCountries();
        $states = $address->country_id
            ? AddressHelper::getStates($address->country_id)
            : collect();

        return view('frontend.addresses.edit', compact('address', 'countries', 'states'));
    }
}


