<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Cart;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Customer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // Guest cart by default
            'customer_id' => null,
            'currency_id' => function () {
                return Currency::firstOrCreate(
                    ['code' => 'USD'],
                    [
                        'name' => 'US Dollar',
                        'exchange_rate' => 1.00,
                        'decimal_places' => 2,
                        'default' => true,
                        'enabled' => true,
                    ]
                )->id;
            },
            'channel_id' => function () {
                return Channel::firstOrCreate(
                    ['handle' => 'webstore'],
                    [
                        'name' => 'Web Store',
                        'url' => 'http://localhost',
                        'default' => true,
                    ]
                )->id;
            },
            'coupon_code' => fake()->optional(0.1)->bothify('CODE-####'),
            'meta' => [],
        ];
    }

    /**
     * Indicate that the cart belongs to a user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            $user = $user ?? User::factory()->create();
            
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * Indicate that the cart belongs to a customer.
     */
    public function forCustomer(?Customer $customer = null): static
    {
        return $this->state(function (array $attributes) use ($customer) {
            $customer = $customer ?? Customer::factory()->create();
            
            return [
                'customer_id' => $customer->id,
            ];
        });
    }

    /**
     * Indicate that the cart has a coupon code.
     */
    public function withCoupon(string $couponCode = null): static
    {
        return $this->state(fn (array $attributes) => [
            'coupon_code' => $couponCode ?? fake()->bothify('CODE-####'),
        ]);
    }

    /**
     * Indicate that the cart is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
        ]);
    }
}

