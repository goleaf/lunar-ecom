<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeder for categories with hierarchical structure.
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📁 Creating categories...');

        // Create top-level categories
        $topLevelCategories = Category::factory()
            ->count(8)
            ->create();

        // Create second-level categories
        foreach ($topLevelCategories as $parent) {
            Category::factory()
                ->count(fake()->numberBetween(3, 6))
                ->withParent($parent)
                ->create();
        }

        // Create some third-level categories
        $secondLevelCategories = Category::whereNotNull('parent_id')->get();
        foreach ($secondLevelCategories->random(10) as $parent) {
            Category::factory()
                ->count(fake()->numberBetween(2, 4))
                ->withParent($parent)
                ->create();
        }

        // Create some inactive categories
        Category::factory()
            ->count(5)
            ->inactive()
            ->create();

        $this->command->info('✅ Categories created!');
    }
}

