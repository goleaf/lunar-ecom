<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\TaxClass;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\TaxClass>
 */
class TaxClassFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = TaxClass::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Standard Tax',
            'default' => false,
        ];
    }

    /**
     * Mark the tax class as default.
     */
    public function defaultClass(): static
    {
        return $this->state(fn () => [
            'default' => true,
        ]);
    }
}
