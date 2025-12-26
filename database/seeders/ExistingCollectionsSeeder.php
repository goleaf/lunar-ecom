<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as SupportCollection;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Language;

/**
 * Ensures there are collections in the database (for storefront /collections page).
 *
 * Creates a default CollectionGroup and a base set of collections if none exist.
 * Collections are created with translated "name" and "description" in attribute_data
 * for all languages present in the system.
 */
class ExistingCollectionsSeeder extends Seeder
{
    public int $minCollections = 12;

    public function run(): void
    {
        $this->command?->info('Ensuring collections exist...');

        $languages = Language::query()->orderBy('id')->get();
        if ($languages->isEmpty()) {
            $this->command?->warn('No languages found. Skipping collection creation.');
            return;
        }

        $group = CollectionGroup::firstOrCreate(
            ['handle' => 'default'],
            [
                'name' => $this->translatedArray($languages, 'Default'),
            ]
        );

        $existingCount = Collection::query()->count();
        if ($existingCount >= $this->minCollections) {
            $this->command?->info("Collections already exist ({$existingCount}).");
            return;
        }

        $toCreate = $this->minCollections - $existingCount;

        $baseNames = [
            'Featured',
            'New Arrivals',
            'Best Sellers',
            'Sale',
            'Electronics',
            'Clothing',
            'Home & Garden',
            'Accessories',
            'Gifts',
            'Outdoor',
            'Office',
            'Kids',
        ];

        $created = 0;
        for ($i = 0; $i < $toCreate; $i++) {
            $name = $baseNames[$i % count($baseNames)];
            if ($i >= count($baseNames)) {
                $name .= ' ' . ($i + 1);
            }

            Collection::create([
                'collection_group_id' => $group->id,
                'type' => 'static',
                'sort' => 'custom',
                'attribute_data' => collect([
                    'name' => $this->translatedField($languages, $name),
                    'description' => $this->translatedField($languages, "Browse {$name} products."),
                ]),
            ]);
            $created++;
        }

        $this->command?->info("✅ Created {$created} collections (group: default).");
    }

    protected function translatedArray(SupportCollection $languages, string $value): array
    {
        $out = [];
        foreach ($languages as $language) {
            $out[$language->code] = $value;
        }
        return $out;
    }

    protected function translatedField(SupportCollection $languages, string $value): TranslatedText
    {
        $translations = [];
        foreach ($languages as $language) {
            $translations[$language->code] = new Text($value);
        }
        return new TranslatedText(collect($translations));
    }
}


