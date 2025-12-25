<?php

namespace Database\Seeders;

use App\Models\SearchAnalytic;
use App\Models\SearchSynonym;
use Illuminate\Database\Seeder;

/**
 * Seeder for search analytics and synonyms.
 */
class SearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔍 Creating search data...');

        // Create search analytics
        SearchAnalytic::factory()
            ->count(200)
            ->create();

        // Create some with results
        SearchAnalytic::factory()
            ->count(150)
            ->withResults()
            ->create();

        // Create some clicked results
        $products = \App\Models\Product::take(50)->get();
        if ($products->isNotEmpty()) {
            SearchAnalytic::factory()
                ->count(100)
                ->create()
                ->each(function ($analytic) use ($products) {
                    if (fake()->boolean(40)) {
                        $analytic->update([
                            'clicked_product_id' => $products->random()->id,
                        ]);
                    }
                });
        }

        // Create search synonyms
        $commonTerms = [
            'laptop' => ['notebook', 'computer', 'pc'],
            'phone' => ['mobile', 'smartphone', 'cellphone'],
            'shoes' => ['footwear', 'sneakers', 'boots'],
            'watch' => ['timepiece', 'timekeeper'],
            'bag' => ['purse', 'handbag', 'tote'],
        ];

        foreach ($commonTerms as $term => $synonyms) {
            SearchSynonym::factory()
                ->withSynonyms($synonyms)
                ->create([
                    'term' => $term,
                ]);
        }

        // Create additional random synonyms
        SearchSynonym::factory()
            ->count(20)
            ->create();

        // Create some inactive synonyms
        SearchSynonym::factory()
            ->count(5)
            ->inactive()
            ->create();

        $this->command->info('✅ Search data created!');
    }
}

