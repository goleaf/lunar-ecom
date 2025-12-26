<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;

/**
 * Ensures every product has translated attribute_data for all system languages.
 *
 * Currently focuses on commonly-used storefront/admin fields that are typically translated:
 * - name
 * - description
 *
 * Strategy:
 * - If a field is already TranslatedText, fill missing locales using the default language value (or first available).
 * - If a field is Text/string, convert it into TranslatedText and copy the same value to all locales.
 *
 * Safe to run multiple times.
 */
class ExistingProductTranslationsSeeder extends Seeder
{
    /**
     * Attribute handles to ensure are present for all locales.
     *
     * NOTE: Keep this conservative. Adding more handles is fine as long as your product type
     * expects them to be translatable in attribute_data.
     *
     * @var string[]
     */
    public array $translatedHandles = [
        'name',
        'description',
    ];

    public function run(): void
    {
        $this->command?->info('Ensuring product translations exist for all languages...');

        $languages = Language::query()->orderBy('id')->get();
        if ($languages->isEmpty()) {
            $this->command?->warn('No languages found. Skipping product translation backfill.');
            return;
        }

        $defaultLanguage = Language::getDefault();
        $defaultCode = $defaultLanguage?->code ?? 'en';

        $updated = 0;

        Product::query()
            ->select(['id', 'attribute_data'])
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($languages, $defaultCode, &$updated) {
                /** @var \App\Models\Product $product */
                foreach ($products as $product) {
                    $attributeData = $product->attribute_data;
                    if (!$attributeData) {
                        // Lunar expects attribute_data to be JSON; keep it as an empty collection.
                        $attributeData = collect();
                    }

                    $changed = false;

                    foreach ($this->translatedHandles as $handle) {
                        $current = $attributeData->get($handle);

                        // Compute a base string to clone into missing locales.
                        $base = $this->baseValue($current, $defaultCode);
                        if ($base === null || $base === '') {
                            continue;
                        }

                        // Normalize to TranslatedText and fill missing locales.
                        $normalized = $this->normalizeToTranslatedText($current, $languages->pluck('code')->all(), $base);

                        // Only write if something actually changes (or if type changes).
                        if (!$this->translatedTextEquals($current, $normalized)) {
                            $attributeData[$handle] = $normalized;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $product->attribute_data = $attributeData;
                        // Avoid triggering model events (versioning, locks, etc.).
                        $product->saveQuietly();
                        $updated++;
                    }
                }
            });

        $this->command?->info("✅ Updated translations for {$updated} products.");
    }

    protected function baseValue(mixed $current, string $defaultCode): ?string
    {
        if ($current instanceof TranslatedText) {
            $translations = $current->getValue(); // collection(locale => Text)
            $default = $translations->get($defaultCode);
            if ($default instanceof Text) {
                return (string) $default->getValue();
            }
            $first = $translations->first();
            if ($first instanceof Text) {
                return (string) $first->getValue();
            }
            return null;
        }

        if ($current instanceof Text) {
            return (string) $current->getValue();
        }

        if (is_string($current)) {
            return $current;
        }

        // Other field types are ignored here on purpose.
        return null;
    }

    /**
     * @param  mixed  $current
     * @param  string[]  $languageCodes
     */
    protected function normalizeToTranslatedText(mixed $current, array $languageCodes, string $base): TranslatedText
    {
        $translations = collect();

        if ($current instanceof TranslatedText) {
            $translations = $current->getValue();
        }

        foreach ($languageCodes as $code) {
            $existing = $translations->get($code);
            if ($existing instanceof Text && (string) $existing->getValue() !== '') {
                continue;
            }
            $translations[$code] = new Text($base);
        }

        return new TranslatedText($translations);
    }

    protected function translatedTextEquals(mixed $a, TranslatedText $b): bool
    {
        if (!$a instanceof TranslatedText) {
            return false;
        }

        $aVals = $a->getValue();
        $bVals = $b->getValue();

        if ($aVals->count() !== $bVals->count()) {
            return false;
        }

        foreach ($bVals as $locale => $text) {
            $aText = $aVals->get($locale);
            if (!$aText instanceof Text || !$text instanceof Text) {
                return false;
            }
            if ((string) $aText->getValue() !== (string) $text->getValue()) {
                return false;
            }
        }

        return true;
    }
}


