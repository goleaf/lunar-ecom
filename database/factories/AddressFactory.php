<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\Customer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'country_id' => function () {
                return Country::firstOrCreate(
                    ['iso2' => 'US'],
                    [
                        'name' => 'United States',
                        'iso3' => 'USA',
                        'iso2' => 'US',
                        'phonecode' => '1',
                        'capital' => 'Washington',
                        'currency' => 'USD',
                        'native' => 'United States',
                        'emoji' => '🇺🇸',
                        'emoji_u' => 'U+1F1FA U+1F1F8',
                    ]
                )->id;
            },
            'title' => fake()->optional()->randomElement(['Mr', 'Mrs', 'Miss', 'Ms', 'Dr']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => fake()->optional(0.3)->company(),
            'line_one' => fake()->streetAddress(),
            'line_two' => fake()->optional(0.3)->secondaryAddress(),
            'line_three' => null,
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postcode' => fake()->postcode(),
            'delivery_instructions' => fake()->optional(0.2)->sentence(),
            'contact_email' => fake()->optional(0.5)->safeEmail(),
            'contact_phone' => fake()->optional(0.5)->phoneNumber(),
            'shipping_default' => false,
            'billing_default' => false,
            'meta' => [],
        ];
    }

    /**
     * Indicate that the address is a shipping default.
     */
    public function shippingDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_default' => true,
        ]);
    }

    /**
     * Indicate that the address is a billing default.
     */
    public function billingDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_default' => true,
        ]);
    }

    /**
     * Set the country.
     */
    public function forCountry(Country|int $country): static
    {
        $countryId = $country instanceof Country ? $country->id : $country;
        
        return $this->state(fn (array $attributes) => [
            'country_id' => $countryId,
        ]);
    }
}

