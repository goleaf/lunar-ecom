<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Lunar\Base\FieldType;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;

/**
 * Backfills attribute_data for existing products so every product has values for
 * all mapped attributes and all system languages.
 *
 * - Does NOT overwrite existing values.
 * - For translated fields, copies the default locale value into missing locales.
 * - Optionally syncs values into lunar_product_attribute_values for filtering.
 */
class BackfillProductAttributeDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! Language::query()->exists()) {
            $this->call(LanguageSeeder::class);
        }

        $locales = Language::query()->pluck('code')->values()->all();
        $defaultLocale = Language::query()->where('default', true)->value('code') ?: (config('app.locale') ?: 'en');

        $total = Product::query()->count();
        $this->command?->info("🔧 Backfilling attribute_data for {$total} products...");

        Product::query()
            ->with(['productType.mappedAttributes'])
            ->chunkById(200, function ($products) use ($locales, $defaultLocale) {
                /** @var \Illuminate\Support\Collection<int, Product> $products */
                foreach ($products as $product) {
                    $this->backfillProduct($product, $locales, $defaultLocale);
                }
            });

        $this->command?->info('✅ Product attribute_data backfill completed.');
    }

    protected function backfillProduct(Product $product, array $locales, string $defaultLocale): void
    {
        $data = $product->attribute_data;
        if (! $data instanceof Collection) {
            $data = collect();
        }

        $changed = false;

        $attributes = $product->productType?->mappedAttributes ?? collect();

        foreach ($attributes as $attribute) {
            if (! $attribute instanceof Attribute) {
                continue;
            }

            $handle = $attribute->handle;
            if (! $handle) {
                continue;
            }

            $expectedType = $attribute->type;

            // If missing entirely, create a sensible default.
            if (! $data->has($handle)) {
                $data->put($handle, $this->makeDefaultFieldType($attribute, $locales, $defaultLocale));
                $changed = true;
                continue;
            }

            $existing = $data->get($handle);

            // Ensure translated fields have all locales.
            if ($expectedType === TranslatedText::class) {
                $updated = $this->ensureTranslatedLocales($existing, $locales, $defaultLocale);
                if ($updated !== $existing) {
                    $data->put($handle, $updated);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $product->attribute_data = $data;
            $product->saveQuietly();
        }

        // Sync values to the attribute values table for filtering/search.
        $this->syncAttributeValues($product, $attributes, $data, $locales, $defaultLocale);
    }

    protected function makeDefaultFieldType(Attribute $attribute, array $locales, string $defaultLocale): FieldType
    {
        $type = $attribute->type;

        if ($type === TranslatedText::class) {
            $base = new Text('');
            $translations = collect();
            foreach ($locales as $locale) {
                $translations->put($locale, $locale === $defaultLocale ? $base : new Text($base->getValue()));
            }
            return new TranslatedText($translations);
        }

        // For non-translated types, instantiate with default value if available.
        $default = null;
        if (is_array($attribute->default_value ?? null)) {
            // If a default is defined as per-locale array, use default locale value.
            $default = ($attribute->default_value[$defaultLocale] ?? $attribute->default_value['en'] ?? null);
        }

        if ($default === null) {
            $default = $attribute->getDefaultValue();
        }

        /** @var FieldType $obj */
        $obj = new $type($default);
        return $obj;
    }

    protected function ensureTranslatedLocales(mixed $existing, array $locales, string $defaultLocale): FieldType
    {
        // If already a TranslatedText, fill missing locales.
        if ($existing instanceof TranslatedText) {
            $translations = $existing->getValue();

            // Sometimes translations can be array-ish; normalize to Collection.
            if (! $translations instanceof Collection) {
                $translations = collect($translations);
            }

            $defaultField = $translations->get($defaultLocale);
            if (! $defaultField instanceof FieldType) {
                // If the default locale isn't a FieldType, fall back to first FieldType.
                $defaultField = $translations->first(fn ($v) => $v instanceof FieldType) ?: new Text('');
            }

            $changed = false;
            foreach ($locales as $locale) {
                if (! $translations->has($locale) || ! ($translations->get($locale) instanceof FieldType)) {
                    $translations->put($locale, new Text((string) ($defaultField->getValue() ?? '')));
                    $changed = true;
                }
            }

            return $changed ? new TranslatedText($translations) : $existing;
        }

        // If not a TranslatedText, wrap it and copy to all locales.
        if ($existing instanceof FieldType) {
            $value = $existing->getValue();
        } else {
            $value = $existing;
        }

        $translations = collect();
        foreach ($locales as $locale) {
            $translations->put($locale, new Text((string) ($value ?? '')));
        }

        return new TranslatedText($translations);
    }

    protected function syncAttributeValues(
        Product $product,
        Collection $attributes,
        Collection $data,
        array $locales,
        string $defaultLocale
    ): void {
        foreach ($attributes as $attribute) {
            if (! $attribute instanceof Attribute) {
                continue;
            }

            $handle = $attribute->handle;
            if (! $handle) {
                continue;
            }

            // Only sync attributes that are filterable/searchable to keep table smaller.
            if (! ($attribute->filterable || $attribute->searchable)) {
                continue;
            }

            $field = $data->get($handle);
            if (! $field instanceof FieldType) {
                continue;
            }

            if ($field instanceof TranslatedText) {
                $translations = $field->getValue();
                if (! $translations instanceof Collection) {
                    $translations = collect($translations);
                }

                foreach ($locales as $locale) {
                    $v = $translations->get($locale);
                    $raw = $v instanceof FieldType ? $v->getValue() : null;

                    ProductAttributeValue::query()->updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'attribute_id' => $attribute->id,
                            'locale' => $locale,
                        ],
                        [
                            'value' => is_array($raw) ? $raw : (string) ($raw ?? ''),
                            'is_override' => false,
                        ]
                    );
                }
            } else {
                $raw = $field->getValue();

                ProductAttributeValue::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attribute_id' => $attribute->id,
                        'locale' => $defaultLocale,
                    ],
                    [
                        'value' => $raw,
                        'is_override' => false,
                    ]
                );
            }
        }
    }
}


