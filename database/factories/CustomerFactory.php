<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Customer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->optional()->randomElement(['Mr', 'Mrs', 'Miss', 'Ms', 'Dr']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => fake()->optional(0.3)->company(),
            'vat_no' => fake()->optional(0.2)->bothify('VAT#######'),
            'account_ref' => fake()->optional(0.2)->bothify('ACC-####-???'),
            'meta' => [],
        ];
    }

    /**
     * Indicate that the customer has a company.
     */
    public function withCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => fake()->company(),
            'vat_no' => fake()->bothify('VAT#######'),
        ]);
    }

    /**
     * Indicate that the customer is associated with a user.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            $user = $user ?? User::factory()->create();
            
            return [];
        })->afterCreating(function (Customer $customer) use ($user) {
            if ($user) {
                $customer->users()->attach($user->id);
            }
        });
    }
}

