<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Lunar\Addresses\AddressHelper;
use App\Lunar\Customers\CustomerHelper;
use Illuminate\Http\Request;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\State;

class AddressController extends Controller
{
    /**
     * Display a listing of the customer's addresses.
     */
    public function index()
    {
        // Policy check ensures user is authenticated and can view addresses
        $this->authorize('viewAny', Address::class);
        
        $user = auth()->user();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);
        $addresses = AddressHelper::getForCustomer($customer->id)->load('country');
        $defaultShipping = AddressHelper::getDefaultShipping($customer->id);
        $defaultBilling = AddressHelper::getDefaultBilling($customer->id);

        return view('frontend.addresses.index', compact('addresses', 'defaultShipping', 'defaultBilling'));
    }

    /**
     * Show the form for creating a new address.
     */
    public function create()
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $countries = AddressHelper::getCountries();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);

        return view('frontend.addresses.create', compact('countries', 'customer'));
    }

    /**
     * Store a newly created address.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'line_one' => 'required|string|max:255',
            'line_two' => 'nullable|string|max:255',
            'line_three' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'delivery_instructions' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'shipping_default' => 'nullable|boolean',
            'billing_default' => 'nullable|boolean',
        ]);

        $customer = CustomerHelper::getOrCreateCustomerForUser($user);

        // Create the address
        $address = AddressHelper::create($customer->id, $validated);

        // Set as default if requested
        if ($request->has('shipping_default') && $request->shipping_default) {
            AddressHelper::setDefaultShipping($address);
        }

        if ($request->has('billing_default') && $request->billing_default) {
            AddressHelper::setDefaultBilling($address);
        }

        return redirect()->route('frontend.addresses.index')
            ->with('success', __('frontend.messages.address_added'));
    }

    /**
     * Show the form for editing the specified address.
     */
    public function edit(Address $address)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $this->authorize('update', $address);

        $countries = AddressHelper::getCountries();
        $states = $address->country_id 
            ? AddressHelper::getStates($address->country_id) 
            : collect();

        return view('frontend.addresses.edit', compact('address', 'countries', 'states'));
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, Address $address)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $this->authorize('update', $address);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'line_one' => 'required|string|max:255',
            'line_two' => 'nullable|string|max:255',
            'line_three' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'delivery_instructions' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'shipping_default' => 'nullable|boolean',
            'billing_default' => 'nullable|boolean',
        ]);

        // Update the address
        $address->update($validated);

        // Set as default if requested
        if ($request->has('shipping_default') && $request->shipping_default) {
            AddressHelper::setDefaultShipping($address);
        } elseif ($address->shipping_default && !$request->shipping_default) {
            // Unset if it was default but now not requested
            $address->update(['shipping_default' => false]);
        }

        if ($request->has('billing_default') && $request->billing_default) {
            AddressHelper::setDefaultBilling($address);
        } elseif ($address->billing_default && !$request->billing_default) {
            // Unset if it was default but now not requested
            $address->update(['billing_default' => false]);
        }

        return redirect()->route('frontend.addresses.index')
            ->with('success', __('frontend.messages.address_updated'));
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Address $address)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $this->authorize('delete', $address);

        $address->delete();

        return redirect()->route('frontend.addresses.index')
            ->with('success', __('frontend.messages.address_deleted'));
    }

    /**
     * Set an address as the default shipping address.
     */
    public function setDefaultShipping(Address $address)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $this->authorize('setDefaultShipping', $address);

        AddressHelper::setDefaultShipping($address);

        return redirect()->route('frontend.addresses.index')
            ->with('success', __('frontend.messages.default_shipping_updated'));
    }

    /**
     * Set an address as the default billing address.
     */
    public function setDefaultBilling(Address $address)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('frontend.messages.login_required'));
        }

        $this->authorize('setDefaultBilling', $address);

        AddressHelper::setDefaultBilling($address);

        return redirect()->route('frontend.addresses.index')
            ->with('success', __('frontend.messages.default_billing_updated'));
    }
}



