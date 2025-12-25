<?php

namespace App\Lunar\Taxation;

use Illuminate\Support\Collection;
use Lunar\Models\Country;
use Lunar\Models\CustomerGroup;
use Lunar\Models\State;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxRate;
use Lunar\Models\TaxRateAmount;
use Lunar\Models\TaxZone;
use Lunar\Models\TaxZoneCountry;
use Lunar\Models\TaxZoneCustomerGroup;
use Lunar\Models\TaxZonePostcode;
use Lunar\Models\TaxZoneState;

/**
 * Helper class for working with Lunar Taxation.
 * 
 * Provides convenience methods for managing tax classes, tax zones, and tax rates.
 * See: https://docs.lunarphp.com/1.x/reference/taxation
 */
class TaxHelper
{
    /**
     * Get the default tax zone.
     * 
     * @return TaxZone|null
     */
    public static function getDefaultTaxZone(): ?TaxZone
    {
        return TaxZone::where('default', true)->first();
    }

    /**
     * Get all active tax zones.
     * 
     * @return Collection<TaxZone>
     */
    public static function getActiveTaxZones(): Collection
    {
        return TaxZone::where('active', true)->get();
    }

    /**
     * Get all tax zones.
     * 
     * @return Collection<TaxZone>
     */
    public static function getAllTaxZones(): Collection
    {
        return TaxZone::all();
    }

    /**
     * Find a tax zone by ID.
     * 
     * @param int $id
     * @return TaxZone|null
     */
    public static function findTaxZone(int $id): ?TaxZone
    {
        return TaxZone::find($id);
    }

    /**
     * Create a tax zone.
     * 
     * @param string $name Zone name (e.g., 'UK', 'US')
     * @param string $zoneType 'country', 'states', or 'postcodes'
     * @param string $priceDisplay 'tax_inclusive' or 'tax_exclusive'
     * @param bool $active Whether the zone is active
     * @param bool $default Whether this is the default tax zone
     * @return TaxZone
     */
    public static function createTaxZone(
        string $name,
        string $zoneType,
        string $priceDisplay,
        bool $active = true,
        bool $default = false
    ): TaxZone {
        // If setting as default, unset any existing default
        if ($default) {
            TaxZone::where('default', true)->update(['default' => false]);
        }

        return TaxZone::create([
            'name' => $name,
            'zone_type' => $zoneType,
            'price_display' => $priceDisplay,
            'active' => $active,
            'default' => $default,
        ]);
    }

    /**
     * Add a country to a tax zone.
     * 
     * @param TaxZone $taxZone
     * @param Country|int $country Country instance or ID
     * @return TaxZoneCountry
     */
    public static function addCountryToTaxZone(TaxZone $taxZone, Country|int $country): TaxZoneCountry
    {
        $countryId = $country instanceof Country ? $country->id : $country;
        
        return $taxZone->countries()->firstOrCreate([
            'country_id' => $countryId,
        ]);
    }

    /**
     * Add a state to a tax zone.
     * 
     * @param TaxZone $taxZone
     * @param State|int $state State instance or ID
     * @return TaxZoneState
     */
    public static function addStateToTaxZone(TaxZone $taxZone, State|int $state): TaxZoneState
    {
        $stateId = $state instanceof State ? $state->id : $state;
        
        return $taxZone->states()->firstOrCreate([
            'state_id' => $stateId,
        ]);
    }

    /**
     * Add a postcode to a tax zone.
     * 
     * @param TaxZone $taxZone
     * @param Country|int $country Country instance or ID
     * @param string $postcode Postcode (can use wildcards, e.g., '9021*')
     * @return TaxZonePostcode
     */
    public static function addPostcodeToTaxZone(TaxZone $taxZone, Country|int $country, string $postcode): TaxZonePostcode
    {
        $countryId = $country instanceof Country ? $country->id : $country;
        
        return $taxZone->postcodes()->firstOrCreate([
            'country_id' => $countryId,
            'postcode' => $postcode,
        ]);
    }

    /**
     * Add a customer group to a tax zone.
     * 
     * @param TaxZone $taxZone
     * @param CustomerGroup|int $customerGroup Customer group instance or ID
     * @return TaxZoneCustomerGroup
     */
    public static function addCustomerGroupToTaxZone(TaxZone $taxZone, CustomerGroup|int $customerGroup): TaxZoneCustomerGroup
    {
        $groupId = $customerGroup instanceof CustomerGroup ? $customerGroup->id : $customerGroup;
        
        return $taxZone->customerGroups()->firstOrCreate([
            'customer_group_id' => $groupId,
        ]);
    }

    /**
     * Create a tax class.
     * 
     * @param string $name Tax class name (e.g., 'Clothing', 'Electronics')
     * @param bool $default Whether this is the default tax class (optional, depends on Lunar version)
     * @return TaxClass
     */
    public static function createTaxClass(string $name, bool $default = false): TaxClass
    {
        $data = ['name' => $name];
        
        // Only add default if the column exists (some Lunar versions may not have it)
        // If setting as default, unset any existing default
        if ($default) {
            TaxClass::where('default', true)->update(['default' => false]);
            $data['default'] = true;
        }
        
        return TaxClass::create($data);
    }

    /**
     * Get all tax classes.
     * 
     * @return Collection<TaxClass>
     */
    public static function getAllTaxClasses(): Collection
    {
        return TaxClass::all();
    }

    /**
     * Get the default tax class.
     * 
     * Note: If the 'default' column doesn't exist, this will return null.
     * 
     * @return TaxClass|null
     */
    public static function getDefaultTaxClass(): ?TaxClass
    {
        try {
            return TaxClass::where('default', true)->first();
        } catch (\Exception $e) {
            // Column may not exist in some Lunar versions
            return TaxClass::first();
        }
    }

    /**
     * Find a tax class by ID.
     * 
     * @param int $id
     * @return TaxClass|null
     */
    public static function findTaxClass(int $id): ?TaxClass
    {
        return TaxClass::find($id);
    }

    /**
     * Create a tax rate for a tax zone.
     * 
     * @param TaxZone|int $taxZone Tax zone instance or ID
     * @param string $name Tax rate name (e.g., 'UK VAT', 'State Tax')
     * @return TaxRate
     */
    public static function createTaxRate(TaxZone|int $taxZone, string $name): TaxRate
    {
        $zoneId = $taxZone instanceof TaxZone ? $taxZone->id : $taxZone;
        
        return TaxRate::create([
            'tax_zone_id' => $zoneId,
            'name' => $name,
        ]);
    }

    /**
     * Add a tax rate amount (percentage) for a tax class.
     * 
     * @param TaxRate|int $taxRate Tax rate instance or ID
     * @param TaxClass|int $taxClass Tax class instance or ID
     * @param float $percentage Tax percentage (e.g., 6.0 for 6%)
     * @return TaxRateAmount
     */
    public static function addTaxRateAmount(TaxRate|int $taxRate, TaxClass|int $taxClass, float $percentage): TaxRateAmount
    {
        $rateId = $taxRate instanceof TaxRate ? $taxRate->id : $taxRate;
        $classId = $taxClass instanceof TaxClass ? $taxClass->id : $taxClass;
        
        return TaxRateAmount::firstOrCreate([
            'tax_rate_id' => $rateId,
            'tax_class_id' => $classId,
        ], [
            'percentage' => $percentage,
        ]);
    }

    /**
     * Get tax rates for a tax zone.
     * 
     * @param TaxZone|int $taxZone Tax zone instance or ID
     * @return Collection<TaxRate>
     */
    public static function getTaxRatesForZone(TaxZone|int $taxZone): Collection
    {
        $zoneId = $taxZone instanceof TaxZone ? $taxZone->id : $taxZone;
        
        return TaxRate::where('tax_zone_id', $zoneId)->get();
    }

    /**
     * Get tax rate amounts for a tax rate.
     * 
     * @param TaxRate|int $taxRate Tax rate instance or ID
     * @return Collection<TaxRateAmount>
     */
    public static function getTaxRateAmounts(TaxRate|int $taxRate): Collection
    {
        $rateId = $taxRate instanceof TaxRate ? $taxRate->id : $taxRate;
        
        return TaxRateAmount::where('tax_rate_id', $rateId)->get();
    }

    /**
     * Get tax percentage for a tax rate and tax class combination.
     * 
     * @param TaxRate|int $taxRate Tax rate instance or ID
     * @param TaxClass|int $taxClass Tax class instance or ID
     * @return float|null Tax percentage, or null if not found
     */
    public static function getTaxPercentage(TaxRate|int $taxRate, TaxClass|int $taxClass): ?float
    {
        $rateId = $taxRate instanceof TaxRate ? $taxRate->id : $taxRate;
        $classId = $taxClass instanceof TaxClass ? $taxClass->id : $taxClass;
        
        $amount = TaxRateAmount::where('tax_rate_id', $rateId)
            ->where('tax_class_id', $classId)
            ->first();
        
        return $amount?->percentage;
    }
}

