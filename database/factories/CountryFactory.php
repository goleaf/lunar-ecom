<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lunar\Models\Country;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Country>
 */
class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $iso2 = Str::upper(fake()->countryCode());
        $iso3 = Str::upper(fake()->lexify('???'));

        return [
            'name' => fake()->country(),
            'iso3' => $iso3,
            'iso2' => $iso2,
            'phonecode' => (string) fake()->numberBetween(1, 999),
            'capital' => fake()->city(),
            'currency' => 'USD',
            'native' => null,
            'emoji' => '??',
            'emoji_u' => 'U+003F',
        ];
    }
}
