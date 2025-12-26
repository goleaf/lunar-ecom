<?php

namespace Database\Factories;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductType>
 */
class ProductTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProductType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        
        return [
            'name' => $name,
        ];
    }

    /**
     * Create a simple product type.
     */
    public function simple(): static
    {
        return $this->state(fn () => ['name' => 'simple']);
    }

    /**
     * Create a configurable product type.
     */
    public function configurable(): static
    {
        return $this->state(fn () => ['name' => 'configurable']);
    }

    /**
     * Create a bundle product type.
     */
    public function bundle(): static
    {
        return $this->state(fn () => ['name' => 'bundle']);
    }

    /**
     * Create a digital product type.
     */
    public function digital(): static
    {
        return $this->state(fn () => ['name' => 'digital']);
    }

    /**
     * Create a service product type.
     */
    public function service(): static
    {
        return $this->state(fn () => ['name' => 'service']);
    }
}
