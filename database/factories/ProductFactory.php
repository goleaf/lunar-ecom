<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductType;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Brand;
use Lunar\Models\Language;
use Lunar\Models\Url;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        $languageCodes = Language::query()->orderBy('id')->pluck('code')->all();
        if (empty($languageCodes)) {
            $languageCodes = ['en'];
        }

        $translatedName = collect();
        foreach ($languageCodes as $code) {
            // Keep it deterministic-ish and easy to spot in admin/frontend when switching locales.
            $translatedName[$code] = new Text($code === 'en' ? $name : "{$name} ({$code})");
        }

        $baseDescription = fake()->paragraph();
        $translatedDescription = collect();
        foreach ($languageCodes as $code) {
            $translatedDescription[$code] = new Text($code === 'en' ? $baseDescription : "{$baseDescription} ({$code})");
        }

        $status = fake()->randomElement([
            Product::STATUS_PUBLISHED,
            Product::STATUS_ACTIVE,
            Product::STATUS_DRAFT,
            Product::STATUS_ARCHIVED,
            Product::STATUS_DISCONTINUED,
        ]);

        $visibility = fake()->randomElement([
            Product::VISIBILITY_PUBLIC,
            Product::VISIBILITY_PRIVATE,
            Product::VISIBILITY_SCHEDULED,
        ]);

        $publishedAt = in_array($status, [Product::STATUS_PUBLISHED, Product::STATUS_ACTIVE], true)
            ? Carbon::instance(fake()->dateTimeBetween('-6 months', 'now'))
            : null;

        $scheduledPublishAt = $visibility === Product::VISIBILITY_SCHEDULED
            ? Carbon::instance(fake()->dateTimeBetween('+1 day', '+1 month'))
            : null;

        $scheduledUnpublishAt = $scheduledPublishAt
            ? (clone $scheduledPublishAt)->addDays(fake()->numberBetween(3, 30))
            : null;

        return [
            'product_type_id' => ProductType::factory(),
            'status' => $status,
            'visibility' => $visibility,
            'short_description' => fake()->sentence(),
            'full_description' => fake()->paragraphs(3, true),
            'technical_description' => fake()->optional(0.5)->paragraph(),
            'meta_title' => fake()->optional(0.7)->sentence(6),
            'meta_description' => fake()->optional(0.7)->sentence(12),
            'meta_keywords' => fake()->optional(0.5)->words(6, true),
            'published_at' => $publishedAt,
            'scheduled_publish_at' => $scheduledPublishAt,
            'scheduled_unpublish_at' => $scheduledUnpublishAt,
            'version' => 1,
            'is_locked' => false,
            'attribute_data' => collect([
                'name' => new TranslatedText($translatedName),
                'description' => new TranslatedText($translatedDescription),
            ]),
            'brand_id' => null, // Brands are optional, can be set via withBrand() method
        ];
    }

    /**
     * Indicate that the product is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_PUBLISHED,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the product is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_DRAFT,
            'visibility' => Product::VISIBILITY_PRIVATE,
            'published_at' => null,
        ]);
    }

    /**
     * Add additional attributes to the product.
     */
    public function withAttributes(array $attributes): static
    {
        return $this->state(function (array $defaultAttributes) use ($attributes) {
            $attributeData = $defaultAttributes['attribute_data'] ?? collect();

            foreach ($attributes as $key => $value) {
                if (is_string($value)) {
                    $attributeData[$key] = new Text($value);
                } elseif (is_object($value) && method_exists($value, 'getValue')) {
                    // Check if it's a FieldType by checking for getValue method
                    $attributeData[$key] = $value;
                }
            }

            return [
                'attribute_data' => $attributeData,
            ];
        });
    }

    /**
     * Configure the factory to create products with default relations.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            // Attach to default channel if it exists.
            $channel = \Lunar\Models\Channel::where('default', true)->first();
            if ($channel) {
                $product->channels()->syncWithoutDetaching([$channel->id]);
            }

            // Ensure URLs exist for each language (unique per locale).
            $languages = Language::query()->orderBy('id')->get();
            if ($languages->isEmpty()) {
                $languages = collect([
                    Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true])
                ]);
            }

            foreach ($languages as $language) {
                $baseSlug = Str::slug($product->translateAttribute('name', $language->code) ?? $product->id);
                $slug = $baseSlug;

                $suffix = 1;
                while (Url::query()
                    ->where('language_id', $language->id)
                    ->where('slug', $slug)
                    ->exists()
                ) {
                    $slug = "{$baseSlug}-{$suffix}";
                    $suffix++;
                }

                Url::firstOrCreate(
                    [
                        'language_id' => $language->id,
                        'slug' => $slug,
                        'element_type' => Product::morphName(),
                        'element_id' => $product->id,
                    ],
                    [
                        'default' => $language->default ?? false,
                    ]
                );
            }
        });
    }

    /**
     * Indicate that the product has a brand.
     */
    public function withBrand(Brand|string|null $brand = null): static
    {
        return $this->state(function (array $attributes) use ($brand) {
            if ($brand instanceof Brand) {
                return ['brand_id' => $brand->id];
            }

            if (is_string($brand)) {
                $brandModel = Brand::query()->where('name', $brand)->first();
                if (!$brandModel) {
                    $brandModel = BrandFactory::new()->withProfile($brand)->create();
                }

                return ['brand_id' => $brandModel->id];
            }

            // Use an existing brand if available, otherwise create one.
            $existingBrandId = Brand::query()->inRandomOrder()->value('id');
            if ($existingBrandId) {
                return ['brand_id' => $existingBrandId];
            }

            $brandModel = BrandFactory::new()->create();

            return ['brand_id' => $brandModel->id];
        });
    }

    /**
     * Indicate that the product is scheduled.
     */
    public function scheduled(?\DateTime $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_DRAFT,
            'visibility' => Product::VISIBILITY_SCHEDULED,
            'scheduled_publish_at' => $date ? Carbon::instance($date) : now()->addDay(),
            'scheduled_unpublish_at' => null,
        ]);
    }

    /**
     * Indicate that the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Indicate that the product is discontinued.
     */
    public function discontinued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_DISCONTINUED,
        ]);
    }

    /**
     * Indicate that the product represents a bundle.
     */
    public function bundle(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bundle' => true,
            'status' => Product::STATUS_PUBLISHED,
        ]);
    }
}
