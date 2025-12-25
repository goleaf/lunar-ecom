<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Order;
use Lunar\Models\Transaction;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(1000, 100000); // In cents
        
        return [
            'order_id' => Order::factory(),
            'success' => true,
            'type' => 'capture',
            'driver' => fake()->randomElement(['stripe', 'paypal', 'manual']),
            'amount' => $amount,
            'reference' => fake()->unique()->bothify('TXN-####-???'),
            'status' => fake()->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'notes' => fake()->optional(0.3)->sentence(),
            'card_type' => fake()->randomElement(['visa', 'mastercard', 'amex']),
            'last_four' => fake()->numberBetween(1000, 9999),
            'meta' => [],
        ];
    }

    /**
     * Indicate that the transaction is successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => true,
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the transaction is a refund.
     */
    public function asRefund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'refund',
            'status' => 'refunded',
        ]);
    }

    /**
     * Set the payment driver.
     */
    public function driver(string $driver): static
    {
        return $this->state(fn (array $attributes) => [
            'driver' => $driver,
        ]);
    }
}

