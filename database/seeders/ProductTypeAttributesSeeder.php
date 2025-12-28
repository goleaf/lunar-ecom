<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Language;
use Lunar\Models\ProductVariant;

/**
 * Ensures product + variant attributes exist for ALL product types, and that
 * attribute/group names are present for all system languages.
 *
 * Idempotent: safe to run multiple times.
 */
class ProductTypeAttributesSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure languages exist (project uses LanguageSeeder for lt/en/es/fr/de).
        if (! Language::query()->exists()) {
            $this->call(LanguageSeeder::class);
        }

        $locales = Language::query()->pluck('code')->values()->all();
        $defaultLocale = Language::query()->where('default', true)->value('code') ?: (config('app.locale') ?: 'en');

        $productMorph = Product::morphName();
        $variantMorph = ProductVariant::morphName();

        // Attribute groups (by morph key, not class name).
        $groups = [
            // Product groups
            [
                'handle' => 'product',
                'attributable_type' => $productMorph,
                'position' => 0,
                'name' => $this->allLocales('Product', $locales),
            ],
            [
                'handle' => 'filters',
                'attributable_type' => $productMorph,
                'position' => 1,
                'name' => $this->allLocales('Filters', $locales),
            ],
            [
                'handle' => 'specifications',
                'attributable_type' => $productMorph,
                'position' => 2,
                'name' => $this->allLocales('Specifications', $locales),
            ],
            [
                'handle' => 'seo',
                'attributable_type' => $productMorph,
                'position' => 3,
                'name' => $this->allLocales('SEO', $locales),
            ],
            // Variant groups
            [
                'handle' => 'variant',
                'attributable_type' => $variantMorph,
                'position' => 0,
                'name' => $this->allLocales('Variant', $locales),
            ],
        ];

        $groupIds = [];
        foreach ($groups as $group) {
            /**
             * AttributeGroup handles are globally unique in Lunar (not scoped by attributable_type),
             * so we must lookup by handle only and then normalize the attributable_type.
             */
            $record = AttributeGroup::query()->firstOrCreate(
                ['handle' => $group['handle']],
                [
                    'attributable_type' => $group['attributable_type'],
                    'name' => $group['name'],
                    'position' => $group['position'],
                ]
            );

            // Normalize attributable_type to the expected morph key.
            if ($record->attributable_type !== $group['attributable_type']) {
                $record->update(['attributable_type' => $group['attributable_type']]);
            }

            // Ensure all locale keys exist on the group's name.
            $existingName = $record->name ?? [];
            if (! is_array($existingName)) {
                $existingName = [];
            }
            $mergedName = array_merge($this->allLocales($existingName[$defaultLocale] ?? ($existingName['en'] ?? $group['name'][$defaultLocale] ?? $group['name']['en'] ?? $group['handle']), $locales), $existingName);
            if ($mergedName !== $existingName) {
                $record->update(['name' => $mergedName]);
            }

            $groupIds[$group['attributable_type']][$group['handle']] = $record->id;
        }

        // Define attribute specs.
        // Note: attribute_type must match the morph key (product/product_variant) for Lunar admin mapping.
        $attributeSpecs = [
            // Core product content
            $this->attrSpec($productMorph, 'name', $groupIds[$productMorph]['product'], 0, 'Name', \Lunar\FieldTypes\TranslatedText::class, true, true, false),
            $this->attrSpec($productMorph, 'description', $groupIds[$productMorph]['product'], 1, 'Description', \Lunar\FieldTypes\TranslatedText::class, true, true, false),
            $this->attrSpec($productMorph, 'short_description', $groupIds[$productMorph]['product'], 2, 'Short Description', \Lunar\FieldTypes\TranslatedText::class, true, true, false),

            // Specs
            $this->attrSpec($productMorph, 'material', $groupIds[$productMorph]['specifications'], 0, 'Material', \Lunar\FieldTypes\TranslatedText::class, true, true, true),
            $this->attrSpec($productMorph, 'features', $groupIds[$productMorph]['specifications'], 1, 'Features', \Lunar\FieldTypes\TranslatedText::class, true, true, true),
            $this->attrSpec($productMorph, 'weight', $groupIds[$productMorph]['specifications'], 2, 'Weight', \Lunar\FieldTypes\Number::class, false, false, true, ['unit' => 'kg']),
            $this->attrSpec($productMorph, 'warranty_period', $groupIds[$productMorph]['specifications'], 3, 'Warranty Period', \Lunar\FieldTypes\Number::class, false, false, true, ['unit' => 'months']),

            // Filters (product-level)
            $this->attrSpec($productMorph, 'condition', $groupIds[$productMorph]['filters'], 0, 'Condition', \Lunar\FieldTypes\Text::class, false, true, true),

            // SEO
            $this->attrSpec($productMorph, 'meta_title', $groupIds[$productMorph]['seo'], 0, 'Meta Title', \Lunar\FieldTypes\TranslatedText::class, true, true, false),
            $this->attrSpec($productMorph, 'meta_description', $groupIds[$productMorph]['seo'], 1, 'Meta Description', \Lunar\FieldTypes\TranslatedText::class, true, true, false),
            $this->attrSpec($productMorph, 'meta_keywords', $groupIds[$productMorph]['seo'], 2, 'Meta Keywords', \Lunar\FieldTypes\TranslatedText::class, true, true, false),

            // Variant options (variant-level)
            $this->attrSpec($variantMorph, 'color', $groupIds[$variantMorph]['variant'], 0, 'Color', \Lunar\FieldTypes\Text::class, false, true, true),
            $this->attrSpec($variantMorph, 'size', $groupIds[$variantMorph]['variant'], 1, 'Size', \Lunar\FieldTypes\Text::class, false, true, true),
        ];

        $productAttributeIds = [];
        $variantAttributeIds = [];

        foreach ($attributeSpecs as $spec) {
            /** @var Attribute $attr */
            $attr = Attribute::query()->updateOrCreate(
                [
                    'attribute_type' => $spec['attribute_type'],
                    'handle' => $spec['handle'],
                ],
                [
                    'attribute_group_id' => $spec['attribute_group_id'],
                    'position' => $spec['position'],
                    'name' => $this->allLocales($spec['label'], $locales),
                    // Lunar attributes require configuration to be non-null in some DBs.
                    'configuration' => [],
                    'type' => $spec['type'],
                    'required' => $spec['required'],
                    'searchable' => $spec['searchable'],
                    'filterable' => $spec['filterable'],
                    'system' => false,
                    'section' => 'main',
                    // Custom fields (App\Models\Attribute extends Lunar)
                    'localizable' => $spec['localizable'],
                    'unit' => $spec['unit'],
                ]
            );

            // Ensure name has all locale keys.
            $existingName = $attr->name ?? [];
            if (! is_array($existingName)) {
                $existingName = [];
            }
            $mergedName = array_merge($this->allLocales($existingName[$defaultLocale] ?? ($existingName['en'] ?? $spec['label']), $locales), $existingName);
            if ($mergedName !== $existingName) {
                $attr->update(['name' => $mergedName]);
            }

            if ($spec['attribute_type'] === $productMorph) {
                $productAttributeIds[] = $attr->id;
            }
            if ($spec['attribute_type'] === $variantMorph) {
                $variantAttributeIds[] = $attr->id;
            }
        }

        // Re-resolve IDs from DB to avoid any null/invalid keys during sync.
        $productAttributeIds = Attribute::query()
            ->where('attribute_type', $productMorph)
            ->whereIn('handle', collect($attributeSpecs)->where('attribute_type', $productMorph)->pluck('handle')->all())
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $variantAttributeIds = Attribute::query()
            ->where('attribute_type', $variantMorph)
            ->whereIn('handle', collect($attributeSpecs)->where('attribute_type', $variantMorph)->pluck('handle')->all())
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Attach to every product type.
        $productTypes = ProductType::query()->get();
        foreach ($productTypes as $productType) {
            $ids = array_values(array_unique([
                ...$productAttributeIds,
                ...$variantAttributeIds,
            ]));

            if (! empty($ids)) {
                $productType->mappedAttributes()->syncWithoutDetaching(
                    array_fill_keys($ids, [])
                );
            }
        }

        $this->command?->info("✅ Ensured attributes for {$productTypes->count()} product types across ".count($locales).' languages.');
    }

    /**
     * @return array<string,mixed>
     */
    protected function attrSpec(
        string $attributeType,
        string $handle,
        int $attributeGroupId,
        int $position,
        string $label,
        string $type,
        bool $localizable,
        bool $searchable,
        bool $filterable,
        array $extra = []
    ): array {
        return [
            'attribute_type' => $attributeType,
            'handle' => $handle,
            'attribute_group_id' => $attributeGroupId,
            'position' => $position,
            'label' => $label,
            'type' => $type,
            'localizable' => $localizable,
            'searchable' => $searchable,
            'filterable' => $filterable,
            'required' => false,
            'unit' => $extra['unit'] ?? null,
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function allLocales(string $value, array $locales): array
    {
        $out = [];
        foreach ($locales as $locale) {
            $out[$locale] = $value;
        }
        return $out;
    }
}

