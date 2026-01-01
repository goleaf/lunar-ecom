# Seeding (all models, all relations, all translations)

This project has a comprehensive, **idempotent** seeding pipeline that aims to ensure:

- **Every `App\Models\*` model** has at least a small amount of data
- **Every relation** is populated where possible (including pivot tables)
- **All configured locales** are present for translatable fields (including locale-map JSON and Lunar `TranslatedText`)

## One command (recommended)

Run a clean database + full seeding:

```bash
php artisan migrate:fresh --seed
```

This executes `Database\Seeders\DatabaseSeeder`, which calls the seeders below in order.

## Seeders involved

### Feature/domain models + relations

- `database/seeders/FeatureModelsSeeder.php`

Seeds feature-specific tables/relations used by the admin UI (B2B, inventory, customizations, badges, checkout locks, etc.).

### Catch-all: every `App\Models\**` + relations/pivots

- `database/seeders/AllModelsSeeder.php`

Discovers all models in `app/Models/**` (and also includes Lunar core models from `vendor/lunarphp/core/src/Models` so core Lunar tables don't stay empty), ensures each has rows, links foreign keys, and seeds relations:

- `HasOne` / `HasMany`
- `MorphOne` / `MorphMany`
- `BelongsToMany` (including pivot rows)

Notes:
- For **app models**, factories are used when available.
- For **Lunar core models**, the catch-all seeder uses a safe fallback generator (it intentionally avoids using Lunar's vendor factories, to keep things deterministic and prevent accidental extra records like random language codes).

### Final pass: all locales everywhere possible

- `database/seeders/BackfillAllTranslationsSeeder.php`

Backfills missing locales across the dataset for:

- Lunar `attribute_data` `TranslatedText` values
- Strict locale-map JSON/array fields (keys are locale codes)

## Running individual seeders

```bash
php artisan db:seed --class="Database\\Seeders\\FeatureModelsSeeder"
php artisan db:seed --class="Database\\Seeders\\AllModelsSeeder"
php artisan db:seed --class="Database\\Seeders\\BackfillAllTranslationsSeeder"
```

