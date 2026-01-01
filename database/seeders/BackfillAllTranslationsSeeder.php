<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ChannelMedia;
use App\Models\Collection as ProductCollection;
use App\Models\ReferralLandingTemplate;
use App\Models\VariantMedia;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Schema;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Brand;
use Lunar\Models\Language;
use Illuminate\Support\Str;

/**
 * Backfills translations for all supported locales across the app.
 *
 * Covers:
 * - Lunar attribute_data FieldTypes (TranslatedText) for Collections/Brands (and converts common Text fields to TranslatedText)
 * - JSON locale maps stored as arrays (e.g. Category name/description, media alt_text/caption, landing template content)
 *
 * Safe to run multiple times.
 */
class BackfillAllTranslationsSeeder extends Seeder
{
    /**
     * Keys in Lunar attribute_data we treat as localizable and normalize to TranslatedText.
     *
     * Keep this conservative to avoid changing attribute_data types for non-localizable attributes.
     *
     * @var string[]
     */
    protected array $attributeDataLocalizableKeys = [
        'name',
        'description',
        'short_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function isLikelyLocaleMapField(string $field): bool
    {
        $f = Str::lower($field);

        return in_array($f, [
            'name',
            'label',
            'title',
            'description',
            'short_description',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'alt_text',
            'caption',
            'content',
        ], true);
    }

    public function run(): void
    {
        $this->command?->info('🌐 Backfilling translations (all locales) across models...');

        $languages = Language::query()->orderBy('id')->get();
        if ($languages->isEmpty()) {
            $this->command?->warn('No languages found. Skipping translation backfill.');
            return;
        }

        $locales = $languages->pluck('code')->values()->all();
        $defaultLocale = Language::getDefault()?->code ?? ($locales[0] ?? 'en');

        $this->backfillCategories($locales, $defaultLocale);
        $this->backfillCollections($locales, $defaultLocale);
        $this->backfillBrands($locales, $defaultLocale);
        $this->backfillReferralLandingTemplates($locales, $defaultLocale);
        $this->backfillChannelMedia($locales, $defaultLocale);
        $this->backfillVariantMedia($locales, $defaultLocale);
        $this->backfillAllModelsLocaleMaps($locales, $defaultLocale);

        $this->command?->info('✅ Translation backfill completed.');
    }

    protected function backfillCategories(array $locales, string $defaultLocale): void
    {
        $table = (new Category())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $updated = 0;

        Category::query()
            ->select(['id', 'name', 'description'])
            ->orderBy('id')
            ->chunkById(200, function ($categories) use ($locales, $defaultLocale, &$updated) {
                foreach ($categories as $category) {
                    $changed = false;

                    $name = $category->name;
                    if (is_array($name)) {
                        $filled = $this->fillLocaleMap($name, $locales, $defaultLocale);
                        if ($filled !== $name) {
                            $category->name = $filled;
                            $changed = true;
                        }
                    }

                    $description = $category->description;
                    if (is_array($description)) {
                        $filled = $this->fillLocaleMap($description, $locales, $defaultLocale);
                        if ($filled !== $description) {
                            $category->description = $filled;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        // Avoid triggering nested-set / counting side effects.
                        $category->saveQuietly();
                        $updated++;
                    }
                }
            });

        if ($updated > 0) {
            $this->command?->info("  ✓ Categories updated: {$updated}");
        }
    }

    protected function backfillCollections(array $locales, string $defaultLocale): void
    {
        $table = (new ProductCollection())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $updated = 0;

        ProductCollection::query()
            ->select(['id', 'attribute_data'])
            ->orderBy('id')
            ->chunkById(200, function ($collections) use ($locales, $defaultLocale, &$updated) {
                foreach ($collections as $collection) {
                    /** @var \Illuminate\Support\Collection<string, mixed> $data */
                    $raw = $collection->attribute_data;
                    /** @var array<string, mixed> $dataArray */
                    $dataArray = $raw instanceof SupportCollection ? $raw->all() : [];
                    $data = collect($dataArray);

                    $changed = false;

                    foreach ($data as $key => $value) {
                        // 1) If already TranslatedText, fill missing locales.
                        if ($value instanceof TranslatedText) {
                            $ensured = $this->ensureTranslatedTextLocales($value, $locales, $defaultLocale);
                            if ($ensured !== $value) {
                                $data[$key] = $ensured;
                                $changed = true;
                            }
                            continue;
                        }

                        // 2) Optionally normalize common localizable keys from Text/string to TranslatedText.
                        if (!in_array((string) $key, $this->attributeDataLocalizableKeys, true)) {
                            continue;
                        }

                        $base = $this->fieldTypeStringValue($value);
                        if ($base === null) {
                            continue;
                        }

                        $data[$key] = $this->translatedTextFromBase($base, $locales);
                        $changed = true;
                    }

                    if ($changed) {
                        $collection->attribute_data = $data;
                        $collection->saveQuietly();
                        $updated++;
                    }
                }
            });

        if ($updated > 0) {
            $this->command?->info("  ✓ Collections updated: {$updated}");
        }
    }

    protected function backfillBrands(array $locales, string $defaultLocale): void
    {
        $table = (new Brand())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $updated = 0;

        Brand::query()
            ->select(['id', 'attribute_data'])
            ->orderBy('id')
            ->chunkById(200, function ($brands) use ($locales, $defaultLocale, &$updated) {
                foreach ($brands as $brand) {
                    /** @var \Illuminate\Support\Collection<string, mixed> $data */
                    $raw = $brand->attribute_data;
                    /** @var array<string, mixed> $dataArray */
                    $dataArray = $raw instanceof SupportCollection ? $raw->all() : [];
                    $data = collect($dataArray);

                    $changed = false;

                    // Only touch known localizable field(s) for brands.
                    $current = $data['description'] ?? null;
                    if ($current instanceof TranslatedText) {
                        $ensured = $this->ensureTranslatedTextLocales($current, $locales, $defaultLocale);
                        if ($ensured !== $current) {
                            $data['description'] = $ensured;
                            $changed = true;
                        }
                    } else {
                        $base = $this->fieldTypeStringValue($current);
                        if ($base !== null) {
                            $data['description'] = $this->translatedTextFromBase($base, $locales);
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $brand->attribute_data = $data;
                        $brand->saveQuietly();
                        $updated++;
                    }
                }
            });

        if ($updated > 0) {
            $this->command?->info("  ✓ Brands updated: {$updated}");
        }
    }

    protected function backfillReferralLandingTemplates(array $locales, string $defaultLocale): void
    {
        $table = (new ReferralLandingTemplate())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $updated = 0;

        ReferralLandingTemplate::query()
            ->select(['id', 'supported_locales', 'content'])
            ->orderBy('id')
            ->chunkById(200, function ($templates) use ($locales, $defaultLocale, &$updated) {
                foreach ($templates as $template) {
                    $changed = false;

                    $supported = $template->supported_locales;
                    if (!is_array($supported)) {
                        $supported = [];
                    }

                    // Ensure supported locales contains all current system locales.
                    $supportedMerged = array_values(array_unique(array_merge($supported, $locales)));
                    sort($supportedMerged);
                    if ($supportedMerged !== $supported) {
                        $template->supported_locales = $supportedMerged;
                        $changed = true;
                    }

                    $content = $template->content;
                    if (is_array($content) && $this->looksLikeLocaleMap($content, $locales)) {
                        $filled = $this->fillLocaleMap($content, $locales, $defaultLocale);
                        if ($filled !== $content) {
                            $template->content = $filled;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        // Avoid bumping cache version during seed backfills.
                        $template->saveQuietly();
                        $updated++;
                    }
                }
            });

        if ($updated > 0) {
            $this->command?->info("  ✓ Referral landing templates updated: {$updated}");
        }
    }

    protected function backfillChannelMedia(array $locales, string $defaultLocale): void
    {
        $table = (new ChannelMedia())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $updated = 0;

        ChannelMedia::query()
            ->select(['id', 'alt_text', 'caption'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($locales, $defaultLocale, &$updated) {
                foreach ($rows as $row) {
                    $changed = false;

                    if (is_array($row->alt_text) && $this->looksLikeLocaleMap($row->alt_text, $locales)) {
                        $filled = $this->fillLocaleMap($row->alt_text, $locales, $defaultLocale);
                        if ($filled !== $row->alt_text) {
                            $row->alt_text = $filled;
                            $changed = true;
                        }
                    }

                    if (is_array($row->caption) && $this->looksLikeLocaleMap($row->caption, $locales)) {
                        $filled = $this->fillLocaleMap($row->caption, $locales, $defaultLocale);
                        if ($filled !== $row->caption) {
                            $row->caption = $filled;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $row->saveQuietly();
                        $updated++;
                    }
                }
            });

        if ($updated > 0) {
            $this->command?->info("  ✓ Channel media updated: {$updated}");
        }
    }

    protected function backfillVariantMedia(array $locales, string $defaultLocale): void
    {
        $table = (new VariantMedia())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $updated = 0;

        VariantMedia::query()
            ->select(['id', 'alt_text', 'caption'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($locales, $defaultLocale, &$updated) {
                foreach ($rows as $row) {
                    $changed = false;

                    if (is_array($row->alt_text) && $this->looksLikeLocaleMap($row->alt_text, $locales)) {
                        $filled = $this->fillLocaleMap($row->alt_text, $locales, $defaultLocale);
                        if ($filled !== $row->alt_text) {
                            $row->alt_text = $filled;
                            $changed = true;
                        }
                    }

                    if (is_array($row->caption) && $this->looksLikeLocaleMap($row->caption, $locales)) {
                        $filled = $this->fillLocaleMap($row->caption, $locales, $defaultLocale);
                        if ($filled !== $row->caption) {
                            $row->caption = $filled;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $row->saveQuietly();
                        $updated++;
                    }
                }
            });

        if ($updated > 0) {
            $this->command?->info("  ✓ Variant media updated: {$updated}");
        }
    }

    /**
     * Determine whether an array is likely a locale => value map.
     */
    protected function looksLikeLocaleMap(array $value, array $locales): bool
    {
        if ($value === []) {
            return false;
        }

        $keys = array_keys($value);
        return count(array_intersect($keys, $locales)) > 0;
    }

    /**
     * Fill missing locale keys in a locale map.
     *
     * @param array<string,mixed> $value
     * @return array<string,mixed>
     */
    protected function fillLocaleMap(array $value, array $locales, string $defaultLocale): array
    {
        if (!$this->looksLikeLocaleMap($value, $locales)) {
            return $value;
        }

        $base = $value[$defaultLocale] ?? $value['en'] ?? reset($value);

        $out = $value;
        foreach ($locales as $locale) {
            if (!array_key_exists($locale, $out) || $out[$locale] === null || $out[$locale] === '') {
                $out[$locale] = $base;
            }
        }

        return $out;
    }

    /**
     * Ensure a TranslatedText contains all locale keys, cloning from default (or first available).
     */
    protected function ensureTranslatedTextLocales(TranslatedText $existing, array $locales, string $defaultLocale): TranslatedText
    {
        $translations = $existing->getValue();
        if (!$translations instanceof SupportCollection) {
            $translations = collect($translations);
        }

        $defaultField = $translations->get($defaultLocale);
        if (!$defaultField instanceof Text) {
            $defaultField = $translations->first(fn ($v) => $v instanceof Text) ?: new Text('');
        }

        $changed = false;
        foreach ($locales as $locale) {
            $current = $translations->get($locale);
            if ($current instanceof Text && (string) $current->getValue() !== '') {
                continue;
            }

            $translations->put($locale, new Text((string) ($defaultField->getValue() ?? '')));
            $changed = true;
        }

        return $changed ? new TranslatedText($translations) : $existing;
    }

    /**
     * Convert a FieldType-ish value to a string (when possible).
     */
    protected function fieldTypeStringValue(mixed $value): ?string
    {
        if ($value instanceof Text) {
            return (string) $value->getValue();
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'getValue')) {
            $raw = $value->getValue();
            if (is_string($raw)) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * Build a TranslatedText from a base string for all locales.
     */
    protected function translatedTextFromBase(string $base, array $locales): TranslatedText
    {
        $translations = collect();
        foreach ($locales as $locale) {
            $translations->put($locale, new Text($base));
        }

        return new TranslatedText($translations);
    }

    /**
     * Generic pass: for every app/vendor model we know about, detect locale-map arrays and fill missing locales.
     *
     * This is intentionally conservative: we only touch array-like fields where ALL keys are locales.
     */
    protected function backfillAllModelsLocaleMaps(array $locales, string $defaultLocale): void
    {
        $models = $this->discoverModelClasses();
        foreach ($models as $modelClass) {
            $this->backfillModelLocaleMaps($modelClass, $locales, $defaultLocale);
        }
    }

    /**
     * @return array<int, class-string<Model>>
     */
    protected function discoverModelClasses(): array
    {
        $out = array_merge(
            $this->discoverAppModelClasses(),
            $this->discoverLunarCoreModelClasses()
        );

        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    /**
     * @return array<int, class-string<Model>>
     */
    protected function discoverAppModelClasses(): array
    {
        $base = app_path('Models');
        $out = [];

        if (!is_dir($base)) {
            return $out;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $rel = Str::after($path, $base . DIRECTORY_SEPARATOR);
            $rel = str_replace(['/', '\\'], '\\', $rel);
            $class = 'App\\Models\\' . Str::replaceLast('.php', '', $rel);

            if (!class_exists($class)) {
                continue;
            }
            if (!is_subclass_of($class, Model::class)) {
                continue;
            }

            $out[] = $class;
        }

        sort($out);
        return $out;
    }

    /**
     * @return array<int, class-string<Model>>
     */
    protected function discoverLunarCoreModelClasses(): array
    {
        $base = base_path('vendor/lunarphp/core/src/Models');
        $out = [];

        if (!is_dir($base)) {
            return $out;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $rel = Str::after($path, $base . DIRECTORY_SEPARATOR);
            $rel = str_replace(['/', '\\'], '\\', $rel);
            $class = 'Lunar\\Models\\' . Str::replaceLast('.php', '', $rel);

            if (!class_exists($class)) {
                continue;
            }
            if (!is_subclass_of($class, Model::class)) {
                continue;
            }

            $out[] = $class;
        }

        sort($out);
        return $out;
    }

    /**
     * @param class-string<Model> $modelClass
     */
    protected function backfillModelLocaleMaps(string $modelClass, array $locales, string $defaultLocale): void
    {
        /** @var Model $model */
        $model = new $modelClass();
        $table = $model->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $casts = $model->getCasts();
        $arrayFields = [];
        foreach ($casts as $field => $cast) {
            if ($cast === 'array' || $cast === 'json' || $cast === 'collection') {
                $arrayFields[] = $field;
                continue;
            }

            // Cast classes like AsCollection store JSON but hydrate to Collection.
            if (is_string($cast) && str_contains($cast, 'AsCollection')) {
                $arrayFields[] = $field;
            }

            // Eloquent AsArrayObject (Lunar uses this for translated JSON fields like `name`).
            if (is_string($cast) && str_contains($cast, 'AsArrayObject')) {
                $arrayFields[] = $field;
            }
        }

        // Also handle known locale-map attributes even if not casted as array (rare).
        $arrayFields = array_values(array_unique($arrayFields));
        if (empty($arrayFields)) {
            return;
        }

        $keyName = $model->getKeyName();
        if (!Schema::hasColumn($table, $keyName)) {
            return;
        }

        $modelClass::query()
            ->select(array_merge([$keyName], $arrayFields))
            ->orderBy($keyName)
            ->chunkById(200, function ($rows) use ($arrayFields, $locales, $defaultLocale) {
                foreach ($rows as $row) {
                    $changed = false;

                    foreach ($arrayFields as $field) {
                        $value = $row->{$field};

                        if ($value instanceof SupportCollection) {
                            $value = $value->all();
                        }

                        if ($value instanceof \Illuminate\Database\Eloquent\Casts\ArrayObject) {
                            $value = $value->getArrayCopy();
                        } elseif ($value instanceof \ArrayObject) {
                            $value = $value->getArrayCopy();
                        } elseif ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
                            $value = $value->toArray();
                        }

                        if (!is_array($value)) {
                            // Try to recover from invalid JSON that hydrates to null (e.g. `"foo"` instead of `{"en":"foo"}`).
                            $raw = $row->getRawOriginal($field);
                            if (is_string($raw) && $raw !== '') {
                                $decoded = json_decode($raw, true);
                                if (is_array($decoded)) {
                                    $value = $decoded;
                                } elseif (is_string($decoded) && $this->isLikelyLocaleMapField($field)) {
                                    $value = [$defaultLocale => $decoded];
                                } elseif ($decoded === null && $this->isLikelyLocaleMapField($field)) {
                                    $value = [$defaultLocale => $raw];
                                } else {
                                    continue;
                                }
                            } else {
                                continue;
                            }
                        }

                        if (!$this->isStrictLocaleMap($value, $locales)) {
                            continue;
                        }

                        $filled = $this->fillLocaleMap($value, $locales, $defaultLocale);
                        if ($filled !== $value) {
                            $row->{$field} = $filled;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $row->saveQuietly();
                    }
                }
            }, $keyName);
    }

    /**
     * Strict locale map: all keys must be known locales.
     *
     * @param array<string,mixed> $value
     */
    protected function isStrictLocaleMap(array $value, array $locales): bool
    {
        if ($value === []) {
            return false;
        }

        foreach (array_keys($value) as $k) {
            if (!is_string($k) || !in_array($k, $locales, true)) {
                return false;
            }
        }

        return true;
    }
}

