<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Brand;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        $description = fake()->paragraph();
        $languageCodes = $this->getLanguageCodes();

        return [
            'name' => $name,
            'attribute_data' => collect([
                'description' => $this->buildTranslatedText($description, $languageCodes),
                'website_url' => new Text(fake()->url()),
            ]),
        ];
    }

    /**
     * Provide a branded profile suitable for seeders.
     */
    public function withProfile(string $name, ?string $description = null, ?string $websiteUrl = null): static
    {
        return $this->state(function () use ($name, $description, $websiteUrl) {
            $languageCodes = $this->getLanguageCodes();
            $resolvedDescription = $description ?? fake()->paragraph();

            return [
                'name' => $name,
                'attribute_data' => collect([
                    'description' => $this->buildTranslatedText($resolvedDescription, $languageCodes),
                    'website_url' => new Text($websiteUrl ?? fake()->url()),
                ]),
            ];
        });
    }

    /**
     * Build translated text data for available locales.
     *
     * Keeps non-English locales readable while staying ASCII-friendly for seed data.
     */
    protected function buildTranslatedText(string $value, array $languageCodes): TranslatedText
    {
        $translated = collect();
        foreach ($languageCodes as $code) {
            $translated[$code] = new Text($code === 'en' ? $value : "{$value} ({$code})");
        }

        return new TranslatedText($translated);
    }

    /**
     * Get available language codes, falling back to English.
     *
     * @return array<int, string>
     */
    protected function getLanguageCodes(): array
    {
        $codes = Language::query()->orderBy('id')->pluck('code')->all();
        return $codes ?: ['en'];
    }
}
