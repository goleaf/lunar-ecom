<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\ProductVariant;

class OrderSeeder extends Seeder
{
    /**
     * Seed orders with order lines.
     */
    public function run(): void
    {
        $this->command->info('Seeding orders...');

        $variants = ProductVariant::with('prices')->take(50)->get();

        if ($variants->isEmpty()) {
            $this->command->warn('No product variants found. Please run ProductSeeder first.');
            return;
        }

        $orders = Order::factory()->count(25)->create();

        foreach ($orders as $order) {
            $lineCount = fake()->numberBetween(1, 5);
            $selectedVariants = $variants->random($lineCount);

            $orderSubTotal = 0;
            foreach ($selectedVariants as $variant) {
                $quantity = fake()->numberBetween(1, 3);
                $rawPrice = $variant->prices()->first()?->price;
                $unitPrice = $rawPrice instanceof \Lunar\DataTypes\Price
                    ? (int) $rawPrice->value
                    : (int) ($rawPrice ?? 1000);
                $lineSubTotal = $unitPrice * $quantity;
                $orderSubTotal += $lineSubTotal;

                OrderLine::factory()->create([
                    'order_id' => $order->id,
                    'purchasable_type' => ProductVariant::class,
                    'purchasable_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'sub_total' => $lineSubTotal,
                    'total' => $lineSubTotal,
                ]);
            }

            // Update order totals
            $discountTotal = (int) ($orderSubTotal * 0.1);
            $shippingTotal = fake()->numberBetween(500, 2000);
            $taxTotal = (int) (($orderSubTotal - $discountTotal) * 0.2);
            $total = $orderSubTotal - $discountTotal + $shippingTotal + $taxTotal;

            $order->update([
                'sub_total' => $orderSubTotal,
                'discount_total' => $discountTotal,
                'shipping_total' => $shippingTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);
        }

        $this->command->info("Created {$orders->count()} orders with order lines.");
    }
}

