<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

class CartSeeder extends Seeder
{
    /**
     * Seed carts with cart lines.
     */
    public function run(): void
    {
        $this->command->info('Seeding carts...');

        $variants = ProductVariant::take(50)->get();

        if ($variants->isEmpty()) {
            $this->command->warn('No product variants found. Please run ProductSeeder first.');
            return;
        }

        $carts = Cart::factory()->count(20)->create();

        foreach ($carts as $cart) {
            $lineCount = fake()->numberBetween(1, 5);
            $selectedVariants = $variants->random($lineCount);

            foreach ($selectedVariants as $variant) {
                CartLine::factory()->create([
                    'cart_id' => $cart->id,
                    'purchasable_type' => ProductVariant::class,
                    'purchasable_id' => $variant->id,
                    'quantity' => fake()->numberBetween(1, 5),
                ]);
            }
        }

        $this->command->info("Created {$carts->count()} carts with items.");
    }
}

