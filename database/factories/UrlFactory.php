<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use Lunar\Models\Language;
use Lunar\Models\Url;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Url>
 */
class UrlFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Url::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug();
        
        return [
            'language_id' => function () {
                return Language::firstOrCreate(
                    ['code' => 'en'],
                    [
                        'name' => 'English',
                        'default' => true,
                    ]
                )->id;
            },
            'element_type' => Product::class,
            'element_id' => Product::factory(),
            'slug' => $slug,
            'default' => false,
        ];
    }

    /**
     * Indicate that the URL is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'default' => true,
        ]);
    }

    /**
     * Set the element (product, collection, etc.).
     */
    public function forElement($element): static
    {
        return $this->state(fn (array $attributes) => [
            'element_type' => get_class($element),
            'element_id' => $element->id,
        ]);
    }
}
