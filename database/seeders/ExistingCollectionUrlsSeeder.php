<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Lunar\Models\Collection;
use Lunar\Models\Language;
use Lunar\Models\Url;

/**
 * Ensures every existing collection has a default Url entry for every Language in the system.
 *
 * Uses the model relationship `$collection->urls()` so morph types are stored correctly.
 * Safe to run multiple times.
 */
class ExistingCollectionUrlsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Ensuring collection URLs exist for all languages...');

        $languages = Language::query()->orderBy('id')->get();
        if ($languages->isEmpty()) {
            $this->command?->warn('No languages found. Skipping collection URL seeding.');
            return;
        }

        $created = 0;
        $promoted = 0;

        Collection::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($collections) use ($languages, &$created, &$promoted) {
                foreach ($collections as $collection) {
                    foreach ($languages as $language) {
                        $existingDefault = $collection->urls()
                            ->where('language_id', $language->id)
                            ->where('default', true)
                            ->first();

                        if ($existingDefault) {
                            continue;
                        }

                        $anyExisting = $collection->urls()
                            ->where('language_id', $language->id)
                            ->orderBy('id')
                            ->first();

                        if ($anyExisting) {
                            $anyExisting->update(['default' => true]);
                            $promoted++;
                            continue;
                        }

                        $name = $collection->translateAttribute('name', $language->code);
                        $slugBase = Str::slug((string) $name);
                        if (!$slugBase) {
                            $slugBase = "collection-{$collection->id}";
                        }

                        $slug = $this->uniqueSlugForLanguage($slugBase, $language->id, $collection->id);

                        $collection->urls()->create([
                            'language_id' => $language->id,
                            'slug' => $slug,
                            'default' => true,
                        ]);

                        $created++;
                    }
                }
            });

        $this->command?->info("✅ Created {$created} collection URLs.");
        if ($promoted > 0) {
            $this->command?->info("✅ Promoted {$promoted} existing URLs to default.");
        }
    }

    protected function uniqueSlugForLanguage(string $base, int $languageId, int $collectionId): string
    {
        $slug = Str::limit($base, 240, '');

        if (!$this->slugExists($slug, $languageId)) {
            return $slug;
        }

        $withId = Str::limit("{$slug}-{$collectionId}", 240, '');
        if (!$this->slugExists($withId, $languageId)) {
            return $withId;
        }

        for ($i = 2; $i < 500; $i++) {
            $candidate = Str::limit("{$slug}-{$collectionId}-{$i}", 240, '');
            if (!$this->slugExists($candidate, $languageId)) {
                return $candidate;
            }
        }

        return Str::limit("{$slug}-{$collectionId}-" . Str::lower(Str::random(6)), 240, '');
    }

    protected function slugExists(string $slug, int $languageId): bool
    {
        return Url::query()
            ->where('language_id', $languageId)
            ->where('slug', $slug)
            ->exists();
    }
}


