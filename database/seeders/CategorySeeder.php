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
        $topLevelCategories = collect();
        for ($i = 0; $i < 8; $i++) {
            $categoryData = Category::factory()->make()->toArray();
            $category = Category::create($categoryData);
            // Ensure it's a root node
            if ($category->parent_id === null && (!$category->getLft() || !$category->getRgt())) {
                $category->makeRoot()->save();
            }
            $category->refresh();
            // Verify it has lft/rgt values before proceeding
            if (!$category->getLft() || !$category->getRgt()) {
                Category::fixTree();
                $category->refresh();
            }
            $topLevelCategories->push($category);
        }

        // Create second-level categories
        foreach ($topLevelCategories as $parent) {
            $parent->refresh();
            if ($parent->getLft() && $parent->getRgt()) {
                $childCount = fake()->numberBetween(3, 6);
                for ($j = 0; $j < $childCount; $j++) {
                    $childData = Category::factory()->make()->toArray();
                    unset($childData['parent_id']);
                    $child = Category::create($childData);
                    $parent->appendNode($child);
                }
            }
        }

        // Create some third-level categories
        $secondLevelCategories = Category::whereNotNull('parent_id')->get();
        foreach ($secondLevelCategories->random(min(10, $secondLevelCategories->count())) as $parent) {
            $parent->refresh();
            if ($parent->getLft() && $parent->getRgt()) {
                $childCount = fake()->numberBetween(2, 4);
                for ($j = 0; $j < $childCount; $j++) {
                    $childData = Category::factory()->make()->toArray();
                    unset($childData['parent_id']);
                    $child = Category::create($childData);
                    $parent->appendNode($child);
                }
            }
        }

        // Create some inactive categories
        Category::factory()
            ->count(5)
            ->inactive()
            ->create();

        $this->command->info('✅ Categories created!');
    }
}

