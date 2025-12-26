<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Lunar\Models\Language;
use Lunar\Models\Url;

/**
 * Ensures every existing product has a default Url entry for every Language in the system.
 *
 * - One "default" URL per product per language (creates if missing).
 * - Slugs are derived from product translated name for that language.
 * - Slugs are de-duped per language to avoid collisions.
 *
 * Safe to run multiple times.
 */
class ExistingProductUrlsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Ensuring product URLs exist for all languages...');

        $languages = Language::query()->orderBy('id')->get();
        if ($languages->isEmpty()) {
            $this->command?->warn('No languages found. Skipping product URL seeding.');
            return;
        }

        $created = 0;
        $updatedDefaultFlags = 0;

        Product::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($languages, &$created, &$updatedDefaultFlags) {
                foreach ($products as $product) {
                    foreach ($languages as $language) {
                        // Use relationship so morph types are stored correctly.
                        $existingDefault = $product->urls()
                            ->where('language_id', $language->id)
                            ->where('default', true)
                            ->first();

                        if ($existingDefault) {
                            continue;
                        }

                        // If there are URLs but none marked default, promote the first one.
                        $anyExisting = $product->urls()
                            ->where('language_id', $language->id)
                            ->orderBy('id')
                            ->first();

                        if ($anyExisting) {
                            $anyExisting->update(['default' => true]);
                            $updatedDefaultFlags++;
                            continue;
                        }

                        $name = $product->translateAttribute('name', $language->code);
                        $slugBase = Str::slug((string) $name);
                        if (!$slugBase) {
                            $slugBase = "product-{$product->id}";
                        }

                        $slug = $this->uniqueSlugForLanguage($slugBase, $language->id, $product->id);

                        $product->urls()->create([
                            'language_id' => $language->id,
                            'slug' => $slug,
                            'default' => true,
                        ]);

                        $created++;
                    }
                }
            });

        $this->command?->info("✅ Created {$created} product URLs.");
        if ($updatedDefaultFlags > 0) {
            $this->command?->info("✅ Promoted {$updatedDefaultFlags} existing URLs to default.");
        }
    }

    protected function uniqueSlugForLanguage(string $base, int $languageId, int $productId): string
    {
        $slug = Str::limit($base, 240, '');

        // Reserve the slug if unused.
        if (!$this->slugExists($slug, $languageId)) {
            return $slug;
        }

        // Try product-id suffix.
        $withId = Str::limit("{$slug}-{$productId}", 240, '');
        if (!$this->slugExists($withId, $languageId)) {
            return $withId;
        }

        // Final fallback: incrementing suffix.
        for ($i = 2; $i < 500; $i++) {
            $candidate = Str::limit("{$slug}-{$productId}-{$i}", 240, '');
            if (!$this->slugExists($candidate, $languageId)) {
                return $candidate;
            }
        }

        // Extremely unlikely, but keep it deterministic.
        return Str::limit("{$slug}-{$productId}-" . Str::lower(Str::random(6)), 240, '');
    }

    protected function slugExists(string $slug, int $languageId): bool
    {
        return Url::query()
            ->where('language_id', $languageId)
            ->where('slug', $slug)
            ->exists();
    }
}


