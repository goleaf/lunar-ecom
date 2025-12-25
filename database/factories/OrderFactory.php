<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Base\ValueObjects\Cart\TaxBreakdown;
use Lunar\Base\ValueObjects\Cart\TaxBreakdownAmount;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Customer;
use Lunar\Models\Order;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subTotal = fake()->numberBetween(1000, 100000); // In cents
        $discountTotal = fake()->numberBetween(0, $subTotal / 4);
        $shippingTotal = fake()->numberBetween(500, 5000);
        $taxTotal = (int) (($subTotal - $discountTotal) * 0.2); // 20% tax
        $total = $subTotal - $discountTotal + $shippingTotal + $taxTotal;
        
        // Create tax breakdown
        $currency = Currency::where('default', true)->first() ?? Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1.00,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ]
        );
        
        $taxBreakdown = new TaxBreakdown();
        if ($taxTotal > 0) {
            $taxBreakdown->addAmount(
                new TaxBreakdownAmount(
                    price: new \Lunar\DataTypes\Price($taxTotal, $currency, 1),
                    description: 'Tax',
                    identifier: 'vat',
                    percentage: 20.0
                )
            );
        }

        return [
            'user_id' => null,
            'customer_id' => null,
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
            'status' => fake()->randomElement(['pending', 'paid', 'shipped', 'delivered', 'cancelled']),
            'reference' => fake()->unique()->bothify('ORD-####-???'),
            'customer_reference' => fake()->optional(0.3)->bothify('CUST-####'),
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'discount_breakdown' => [],
            'shipping_total' => $shippingTotal,
            'shipping_breakdown' => [],
            'tax_breakdown' => $taxBreakdown,
            'tax_total' => $taxTotal,
            'total' => $total,
            'notes' => fake()->optional(0.2)->sentence(),
            'currency_code' => 'USD',
            'compare_currency_code' => fake()->optional(0.2)->randomElement(['EUR', 'GBP']),
            'exchange_rate' => fake()->randomFloat(4, 0.8, 1.2),
            'placed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'meta' => [],
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'placed_at' => null,
        ]);
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'placed_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'placed_at' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'placed_at' => now()->subDays(fake()->numberBetween(7, 30)),
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the order belongs to a user.
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
     * Indicate that the order belongs to a customer.
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
}

