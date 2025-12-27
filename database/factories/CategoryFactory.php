<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $baseSlug = str($name)->slug()->toString();
        $slug = $baseSlug;

        // Slugs are unique in `categories`. Seeders/factories may run multiple times
        // in one `--seed` (e.g. `CompleteSeeder` + `CategorySeeder`), so ensure uniqueness.
        $suffix = 2;
        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }
        
        return [
            'name' => [
                'en' => $name,
            ],
            'slug' => $slug,
            'description' => fake()->optional(0.7) ? [
                'en' => fake()->paragraph(),
            ] : null,
            'parent_id' => null,
            'display_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'show_in_navigation' => true,
            'meta_title' => fake()->optional(0.5)->sentence(),
            'meta_description' => fake()->optional(0.5)->paragraph(),
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category has a parent.
     */
    public function withParent(?Category $parent = null): static
    {
        return $this->afterCreating(function (Category $category) use ($parent) {
            if ($parent) {
                // Refresh parent to ensure it has lft/rgt values
                $parent->refresh();
                // Use nested set appendNode method to properly set lft/rgt values
                $parent->appendNode($category);
            } elseif (!$category->parent_id) {
                $parentCategory = Category::factory()->create();
                $parentCategory->refresh();
                $parentCategory->appendNode($category);
            }
        });
    }

    /**
     * Indicate that the category has SEO metadata.
     */
    public function withSeo(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_title' => fake()->sentence(),
            'meta_description' => fake()->paragraph(),
        ]);
    }
}

