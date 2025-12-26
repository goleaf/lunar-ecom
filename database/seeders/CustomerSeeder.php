<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;

class CustomerSeeder extends Seeder
{
    /**
     * Seed customers with addresses.
     */
    public function run(): void
    {
        $this->command->info('Seeding customers...');

        $customerGroups = CustomerGroupSeeder::seed();
        $defaultGroup = $customerGroups[CustomerGroupSeeder::DEFAULT_HANDLE] ?? CustomerGroup::where('default', true)->first();

        $country = Country::firstOrCreate(
            ['iso2' => 'US'],
            [
                'name' => 'United States',
                'iso3' => 'USA',
                'iso2' => 'US',
                'phonecode' => '1',
                'capital' => 'Washington',
                'currency' => 'USD',
                'native' => 'United States',
                'region' => 'Americas',
                'subregion' => 'Northern America',
            ]
        );

        $customers = Customer::factory()->count(30)->create();

        foreach ($customers as $customer) {
            // Attach customers to groups so customer-group pricing/discounts can be tested.
            if ($defaultGroup) {
                $attachIds = [$defaultGroup->id];

                $extraGroupHandles = collect(array_keys($customerGroups))
                    ->reject(fn ($h) => $h === CustomerGroupSeeder::DEFAULT_HANDLE)
                    ->values();

                if ($extraGroupHandles->isNotEmpty() && fake()->boolean(40)) {
                    $extra = $customerGroups[$extraGroupHandles->random()] ?? null;
                    if ($extra) {
                        $attachIds[] = $extra->id;
                    }
                }

                $customer->customerGroups()->syncWithoutDetaching($attachIds);
            }

            // Create 1-3 addresses per customer
            $addressCount = fake()->numberBetween(1, 3);
            $addresses = Address::factory()
                ->count($addressCount)
                ->create([
                    'customer_id' => $customer->id,
                    'country_id' => $country->id,
                ]);

            // Set defaults
            if ($addresses->count() > 0) {
                $addresses->first()->update(['shipping_default' => true]);
            }
            if ($addresses->count() > 1) {
                $addresses->skip(1)->first()->update(['billing_default' => true]);
            }
        }

        $this->command->info("Created {$customers->count()} customers with addresses.");
    }
}

