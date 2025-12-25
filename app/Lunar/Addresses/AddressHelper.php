<?php

namespace App\Lunar\Addresses;

use Illuminate\Support\Collection;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\State;

/**
 * Helper class for working with Lunar Addresses.
 * 
 * Provides convenience methods for managing addresses, countries, and states.
 * See: https://docs.lunarphp.com/1.x/reference/addresses
 */
class AddressHelper
{
    /**
     * Create an address for a customer.
     * 
     * @param int $customerId
     * @param array $data Address data
     * @return Address
     */
    public static function create(int $customerId, array $data): Address
    {
        return Address::create(array_merge([
            'customer_id' => $customerId,
        ], $data));
    }

    /**
     * Get all addresses for a customer.
     * 
     * @param int $customerId
     * @return Collection
     */
    public static function getForCustomer(int $customerId): Collection
    {
        return Address::where('customer_id', $customerId)->get();
    }

    /**
     * Get default shipping address for a customer.
     * 
     * @param int $customerId
     * @return Address|null
     */
    public static function getDefaultShipping(int $customerId): ?Address
    {
        return Address::where('customer_id', $customerId)
            ->where('shipping_default', true)
            ->first();
    }

    /**
     * Get default billing address for a customer.
     * 
     * @param int $customerId
     * @return Address|null
     */
    public static function getDefaultBilling(int $customerId): ?Address
    {
        return Address::where('customer_id', $customerId)
            ->where('billing_default', true)
            ->first();
    }

    /**
     * Set an address as the default shipping address.
     * 
     * This will unset other shipping defaults for the customer.
     * 
     * @param Address $address
     * @return Address
     */
    public static function setDefaultShipping(Address $address): Address
    {
        // Unset other shipping defaults for this customer
        Address::where('customer_id', $address->customer_id)
            ->where('id', '!=', $address->id)
            ->update(['shipping_default' => false]);

        // Set this address as default
        $address->update(['shipping_default' => true]);

        return $address->fresh();
    }

    /**
     * Set an address as the default billing address.
     * 
     * This will unset other billing defaults for the customer.
     * 
     * @param Address $address
     * @return Address
     */
    public static function setDefaultBilling(Address $address): Address
    {
        // Unset other billing defaults for this customer
        Address::where('customer_id', $address->customer_id)
            ->where('id', '!=', $address->id)
            ->update(['billing_default' => false]);

        // Set this address as default
        $address->update(['billing_default' => true]);

        return $address->fresh();
    }

    /**
     * Get all countries.
     * 
     * @return Collection
     */
    public static function getCountries(): Collection
    {
        return Country::orderBy('name')->get();
    }

    /**
     * Get a country by ISO2 code.
     * 
     * @param string $iso2
     * @return Country|null
     */
    public static function getCountryByIso2(string $iso2): ?Country
    {
        return Country::where('iso2', strtoupper($iso2))->first();
    }

    /**
     * Get a country by ISO3 code.
     * 
     * @param string $iso3
     * @return Country|null
     */
    public static function getCountryByIso3(string $iso3): ?Country
    {
        return Country::where('iso3', strtoupper($iso3))->first();
    }

    /**
     * Get states for a country.
     * 
     * @param int|Country $country
     * @return Collection
     */
    public static function getStates(int|Country $country): Collection
    {
        $countryId = $country instanceof Country ? $country->id : $country;
        return State::where('country_id', $countryId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a state by code.
     * 
     * @param int|Country $country
     * @param string $code
     * @return State|null
     */
    public static function getStateByCode(int|Country $country, string $code): ?State
    {
        $countryId = $country instanceof Country ? $country->id : $country;
        return State::where('country_id', $countryId)
            ->where('code', strtoupper($code))
            ->first();
    }

    /**
     * Update the last_used_at timestamp for an address.
     * 
     * @param Address $address
     * @return Address
     */
    public static function markAsUsed(Address $address): Address
    {
        $address->update(['last_used_at' => now()]);
        return $address->fresh();
    }

    /**
     * Format an address as a string.
     * 
     * @param Address $address
     * @return string
     */
    public static function format(Address $address): string
    {
        $lines = [];

        if ($address->company_name) {
            $lines[] = $address->company_name;
        }

        if ($address->title) {
            $lines[] = $address->title . ' ' . $address->first_name . ' ' . $address->last_name;
        } else {
            $lines[] = $address->first_name . ' ' . $address->last_name;
        }

        $lines[] = $address->line_one;

        if ($address->line_two) {
            $lines[] = $address->line_two;
        }

        if ($address->line_three) {
            $lines[] = $address->line_three;
        }

        $cityState = $address->city;
        if ($address->state) {
            $cityState .= ', ' . $address->state;
        }

        if ($address->postcode) {
            $cityState .= ' ' . $address->postcode;
        }

        $lines[] = $cityState;

        if ($address->country) {
            $lines[] = $address->country->name;
        }

        return implode("\n", $lines);
    }
}


