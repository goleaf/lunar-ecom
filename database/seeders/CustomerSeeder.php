<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Seed customers with addresses.
     */
    public function run(): void
    {
        $this->command->info('Seeding customers...');

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
            // Create 1-3 addresses per customer
            $addressCount = fake()->numberBetween(1, 3);
            $addresses = Address::factory()
                ->count($addressCount)
                ->forCountry($country)
                ->create([
                    'customer_id' => $customer->id,
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

