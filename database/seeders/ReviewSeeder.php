<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use App\Models\ReviewMedia;
use Illuminate\Database\Seeder;
use Lunar\Models\Customer;

/**
 * Seeder for reviews, review media, and helpful votes.
 */
class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('⭐ Creating reviews...');

        $products = Product::take(30)->get();
        $customers = Customer::take(50)->get();

        if ($products->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('⚠️  No products or customers found. Please run ProductSeeder and CustomerSeeder first.');
            return;
        }

        // Create reviews for products
        foreach ($products as $product) {
            $reviewCount = fake()->numberBetween(5, 20);
            
            $reviews = Review::factory()
                ->count($reviewCount)
                ->create([
                    'product_id' => $product->id,
                ]);

            // Add media to some reviews
            foreach ($reviews->random(fake()->numberBetween(0, $reviewCount / 2)) as $review) {
                ReviewMedia::factory()
                    ->count(fake()->numberBetween(1, 4))
                    ->create([
                        'review_id' => $review->id,
                    ]);
            }

            // Add helpful votes to some reviews
            foreach ($reviews->random(fake()->numberBetween(0, $reviewCount / 3)) as $review) {
                $voteCount = fake()->numberBetween(1, 10);
                $voters = $customers->random($voteCount);
                
                foreach ($voters as $voter) {
                    ReviewHelpfulVote::factory()
                        ->forCustomer($voter)
                        ->create([
                            'review_id' => $review->id,
                            'is_helpful' => fake()->boolean(80),
                        ]);
                }

                // Update helpful count
                $review->update([
                    'helpful_count' => $review->helpfulVotes()->where('is_helpful', true)->count(),
                ]);
            }
        }

        $this->command->info('✅ Reviews created!');
    }
}

