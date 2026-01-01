<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Models\Language;
use Illuminate\Support\Str;

/**
 * Catch-all seeder which attempts to ensure EVERY App\Models\* model has records,
 * and that FK-style relations are connected (by populating *_id columns).
 *
 * Strategy:
 * - Enumerate all model classes in app/Models.
 * - If a factory exists, use it to top-up rows.
 * - If not, create minimal records via Eloquent with best-effort attribute generation.
 *
 * Safe to run multiple times (only tops-up when counts are below target).
 */
class AllModelsSeeder extends Seeder
{
    /**
     * Target minimum rows per model (defaults to 3).
     *
     * @var array<class-string<Model>, int>
     */
    protected array $targets = [];

    /**
     * Cache: class => table columns.
     *
     * @var array<string, array<string, true>>
     */
    protected array $tableColumns = [];

    /**
     * Cache: model class => existing ids (for FK assignment).
     *
     * @var array<class-string<Model>, array<int, int>>
     */
    protected array $idCache = [];

    /**
     * Cache: table => model class (best-effort).
     *
     * @var array<string, class-string<Model>>
     */
    protected array $tableToModel = [];

    /**
     * Cache: table => column meta.
     *
     * @var array<string, array<string, array{nullable: bool, has_default: bool}>>
     */
    protected array $columnMeta = [];

    /**
     * Cache: table => foreign key map (column => [table, column]).
     *
     * @var array<string, array<string, array{table: string, column: string}>>
     */
    protected array $foreignKeyConstraints = [];

    /**
     * Cache: "table.column" => enum values from schema (sqlite/mysql best-effort).
     *
     * @var array<string, array<int, string>|null>
     */
    protected array $enumCache = [];

    /**
     * Cache: table => CREATE TABLE sql (sqlite).
     *
     * @var array<string, string|null>
     */
    protected array $sqliteCreateSql = [];

    /**
     * Cached locale codes used for seeding locale-map JSON fields.
     *
     * @var array<int, string>|null
     */
    protected ?array $cachedLocales = null;

    /**
     * Hard mapping of FK column => model class (covers Lunar + app models).
     *
     * @var array<string, class-string<Model>>
     */
    protected array $foreignKeyMap = [
        'user_id' => \App\Models\User::class,
        'customer_id' => \Lunar\Models\Customer::class,
        'staff_id' => \Lunar\Admin\Models\Staff::class,
        'brand_id' => \Lunar\Models\Brand::class,
        'product_id' => \App\Models\Product::class,
        'product_variant_id' => \App\Models\ProductVariant::class,
        'collection_id' => \App\Models\Collection::class,
        'category_id' => \App\Models\Category::class,
        'channel_id' => \Lunar\Models\Channel::class,
        'currency_id' => \Lunar\Models\Currency::class,
        'customer_group_id' => \Lunar\Models\CustomerGroup::class,
        'order_id' => \Lunar\Models\Order::class,
        'order_line_id' => \Lunar\Models\OrderLine::class,
        'cart_id' => \Lunar\Models\Cart::class,
        'cart_line_id' => \Lunar\Models\CartLine::class,
        'warehouse_id' => \App\Models\Warehouse::class,
        'inventory_level_id' => \App\Models\InventoryLevel::class,
        'contract_id' => \App\Models\B2BContract::class,
        'price_list_id' => \App\Models\PriceList::class,
        'contract_price_id' => \App\Models\ContractPrice::class,
        'price_matrix_id' => \App\Models\PriceMatrix::class,
        'referral_program_id' => \App\Models\ReferralProgram::class,
        'referral_rule_id' => \App\Models\ReferralRule::class,
        'referral_code_id' => \App\Models\ReferralCode::class,
        'referral_attribution_id' => \App\Models\ReferralAttribution::class,
        'fit_finder_quiz_id' => \App\Models\FitFinderQuiz::class,
        'fit_finder_question_id' => \App\Models\FitFinderQuestion::class,
        'size_guide_id' => \App\Models\SizeGuide::class,
        'size_chart_id' => \App\Models\SizeChart::class,
        'badge_id' => \App\Models\ProductBadge::class,
        'question_id' => \App\Models\ProductQuestion::class,
        'customization_id' => \App\Models\ProductCustomization::class,
        'checkout_lock_id' => \App\Models\CheckoutLock::class,
        'fraud_policy_id' => \App\Models\FraudPolicy::class,
        'device_fingerprint_id' => \App\Models\DeviceFingerprint::class,
        'payment_fingerprint_id' => \App\Models\PaymentFingerprint::class,
        'wallet_id' => \App\Models\Wallet::class,
        // Common Lunar keys.
        'language_id' => \Lunar\Models\Language::class,
        'country_id' => \Lunar\Models\Country::class,
        'tax_class_id' => \Lunar\Models\TaxClass::class,
        'collection_group_id' => \Lunar\Models\CollectionGroup::class,
        'attribute_group_id' => \App\Models\AttributeGroup::class,
        'attribute_id' => \App\Models\Attribute::class,
        'tag_id' => \Lunar\Models\Tag::class,
        'discount_id' => \Lunar\Models\Discount::class,
        'media_id' => \Spatie\MediaLibrary\MediaCollections\Models\Media::class,
    ];

    public function run(): void
    {
        $this->command?->info('🧱 Seeding ALL models (catch-all) ...');

        $t0 = microtime(true);

        $this->targets = $this->defaultTargets();

        $models = $this->discoverModelClasses();
        $this->tableToModel = $this->buildTableToModelMap($models);
        $total = count($models);
        $this->command?->info("  Models discovered: {$total}");

        // First pass: ensure each model has some rows.
        $this->command?->info('  Pass 1/3: ensuring base rows...');
        foreach ($models as $i => $class) {
            $this->ensureModelRows($class);
            if (($i + 1) % 25 === 0) {
                $this->command?->info('   - '.($i + 1).' / '.$total);
            }
        }
        $this->command?->info('  Pass 1 done in '.round(microtime(true) - $t0, 2).'s');

        // Second pass: ensure FK columns are populated where possible.
        $t1 = microtime(true);
        $this->command?->info('  Pass 2/3: backfilling FK columns (sampled)...');
        foreach ($models as $i => $class) {
            $this->backfillForeignKeys($class);
            if (($i + 1) % 50 === 0) {
                $this->command?->info('   - '.($i + 1).' / '.$total);
            }
        }
        $this->command?->info('  Pass 2 done in '.round(microtime(true) - $t1, 2).'s');

        // Third pass: ensure relations (incl. pivot tables) have data.
        $t2 = microtime(true);
        $this->command?->info('  Pass 3/3: seeding relations...');
        foreach ($models as $i => $class) {
            $this->seedRelations($class);
            if (($i + 1) % 50 === 0) {
                $this->command?->info('   - '.($i + 1).' / '.$total);
            }
        }
        $this->command?->info('  Pass 3 done in '.round(microtime(true) - $t2, 2).'s');

        $this->command?->info('✅ AllModelsSeeder completed.');
    }

    /**
     * @return array<class-string<Model>, int>
     */
    protected function defaultTargets(): array
    {
        // Default target for "everything exists" without blowing up DB size.
        $default = 3;

        // Allow per-model overrides here if needed.
        return [
            \App\Models\Product::class => 50,
            \App\Models\ProductVariant::class => 150,
            \App\Models\Category::class => 20,
            \App\Models\Collection::class => 20,
            \App\Models\User::class => 20,
            \Lunar\Models\Customer::class => 20,
            \Lunar\Models\Order::class => 25,
        ] + [];
    }

    /**
     * Discover Eloquent model classes we want to include in the catch-all seeding.
     *
     * Primary target: `App\Models\**`
     * Secondary target: Lunar core models (`Lunar\Models\**`) so core tables don't stay empty.
     *
     * @return array<int, class-string<Model>>
     */
    protected function discoverModelClasses(): array
    {
        $candidates = array_merge(
            $this->discoverAppModelClasses(),
            $this->discoverLunarCoreModelClasses()
        );

        // De-duplicate by table name (prefer the first discovered model for a table).
        // Because App models are discovered first, they win over Lunar models.
        $byTable = [];
        foreach ($candidates as $class) {
            try {
                /** @var Model $m */
                $m = new $class();
                $table = $m->getTable();
            } catch (\Throwable) {
                continue;
            }

            $byTable[$table] ??= $class;
        }

        $out = array_values($byTable);
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
     * @param array<int, class-string<Model>> $models
     * @return array<string, class-string<Model>>
     */
    protected function buildTableToModelMap(array $models): array
    {
        $map = [];

        foreach ($models as $class) {
            try {
                /** @var Model $m */
                $m = new $class();
            } catch (\Throwable) {
                continue;
            }

            try {
                $table = $m->getTable();
            } catch (\Throwable) {
                continue;
            }

            // First-wins to avoid churn when multiple models point at same table.
            $map[$table] ??= $class;
        }

        // Include key non-App models referenced by FK constraints.
        $extras = [
            \Lunar\Models\ProductOption::class,
            \Lunar\Models\ProductOptionValue::class,
            \Lunar\Models\ProductType::class,
            \Lunar\Models\Channel::class,
            \Lunar\Models\Currency::class,
            \Lunar\Models\Language::class,
            \Lunar\Models\CustomerGroup::class,
            \Lunar\Models\CollectionGroup::class,
            \Lunar\Models\Customer::class,
            \Lunar\Models\Order::class,
            \Lunar\Models\OrderLine::class,
            \Lunar\Models\Cart::class,
            \Lunar\Models\CartLine::class,
            \Lunar\Models\Brand::class,
            \Lunar\Models\Tag::class,
            \Lunar\Models\Discount::class,
            \Lunar\Models\Country::class,
            \Lunar\Models\TaxClass::class,
            \Spatie\MediaLibrary\MediaCollections\Models\Media::class,
        ];

        foreach ($extras as $class) {
            if (!class_exists($class) || !is_subclass_of($class, Model::class)) {
                continue;
            }

            try {
                /** @var Model $m */
                $m = new $class();
                $table = $m->getTable();
                $map[$table] ??= $class;
            } catch (\Throwable) {
                continue;
            }
        }

        return $map;
    }

    /**
     * Ensure at least N rows exist for the model class.
     *
     * @param class-string<Model> $modelClass
     */
    protected function ensureModelRows(string $modelClass): void
    {
        $target = $this->targets[$modelClass] ?? 3;

        /** @var Model $probe */
        $probe = new $modelClass();
        $table = $probe->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        // Avoid COUNT(*) on potentially large tables (sqlite is slow).
        $keyName = $probe->getKeyName();
        try {
            if (Schema::hasColumn($table, $keyName)) {
                $existing = $modelClass::query()->select($keyName)->limit($target)->get()->count();
            } else {
                $existing = $modelClass::query()->limit($target)->get()->count();
            }
        } catch (\Throwable) {
            $existing = $modelClass::query()->count();
        }

        $missing = $target - (int) $existing;
        if ($missing <= 0) {
            return;
        }

        // Prefer factories for app models when available.
        $factoryClass = 'Database\\Factories\\'.class_basename($modelClass).'Factory';
        if ($this->shouldUseModelFactory($modelClass) && class_exists($factoryClass) && method_exists($modelClass, 'factory')) {
            try {
                $modelClass::factory()->count($missing)->create();
                $this->flushIdCache($modelClass);
                return;
            } catch (\Throwable $e) {
                $this->command?->warn("  ⚠️ Factory seeding failed for {$modelClass}: {$e->getMessage()}");
                // fall through to fallback
            }
        }

        $maxAttempts = 5;
        for ($i = 0; $i < $missing; $i++) {
            $last = null;
            $created = false;

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                try {
                    $this->createFallbackRow($modelClass);
                    $created = true;
                    break;
                } catch (\Throwable $e) {
                    $last = $e;
                }
            }

            if (!$created) {
                $msg = $last ? $last->getMessage() : 'unknown error';
                $this->command?->warn("  ⚠️ Fallback seeding failed for {$modelClass}: {$msg}");
                break;
            }
        }

        $this->flushIdCache($modelClass);
    }

    /**
     * Best-effort record creation for models without a factory.
     *
     * @param class-string<Model> $modelClass
     */
    protected function createFallbackRow(string $modelClass): void
    {
        /** @var Model $model */
        $model = new $modelClass();

        $table = $model->getTable();
        $cols = $this->columnsForTable($table);
        $meta = $this->columnMetaForTable($table);

        $attrs = [];

        // Use model casts when available.
        $casts = $model->getCasts();

        // Avoid inserting timestamps into tables that don't support them.
        $createdCol = $model->getCreatedAtColumn();
        $updatedCol = $model->getUpdatedAtColumn();
        $usesEloquentTimestamps = $model->usesTimestamps()
            && isset($cols[$createdCol])
            && isset($cols[$updatedCol]);
        if (!$usesEloquentTimestamps) {
            $model->timestamps = false;
        }

        foreach (array_keys($cols) as $col) {
            if ($col === $model->getKeyName() || $col === 'deleted_at') {
                continue;
            }
            if ($usesEloquentTimestamps && ($col === $createdCol || $col === $updatedCol)) {
                continue;
            }

            if (array_key_exists($col, $attrs)) {
                continue;
            }

            $hasMeta = array_key_exists($col, $meta);
            $nullable = $hasMeta ? ($meta[$col]['nullable'] ?? true) : false;
            $hasDefault = $hasMeta ? ($meta[$col]['has_default'] ?? false) : false;

            // Let DB defaults handle defaulted columns.
            if ($hasMeta && $hasDefault) {
                continue;
            }

            // Keep fallback rows minimal: skip nullable columns.
            if ($hasMeta && $nullable) {
                continue;
            }

            // FK: prefer schema constraints, fall back to naming map.
            if (Str::endsWith($col, '_id')) {
                $attrs[$col] = $this->resolveForeignKeyValue($modelClass, $table, $col);
                continue;
            }

            // Morph columns (only when it looks like a morph base).
            if (Str::endsWith($col, '_type')) {
                $base = Str::beforeLast($col, '_type');
                $idCol = $base.'_id';

                if (isset($cols[$idCol]) && $this->isLikelyMorphBase($base)) {
                    $attrs[$col] = \App\Models\Product::class;
                    $attrs[$idCol] = $attrs[$idCol]
                        ?? (\App\Models\Product::query()->inRandomOrder()->value('id') ?? null);
                    continue;
                }
            }

            // Enum-like columns (sqlite/mysql best-effort).
            $enumValues = $this->enumValuesForColumn($table, $col);
            if (is_array($enumValues) && !empty($enumValues)) {
                $attrs[$col] = $enumValues[array_rand($enumValues)];
                continue;
            }

            $cast = $casts[$col] ?? null;

            // Backed enum casts.
            if (is_string($cast) && enum_exists($cast) && is_subclass_of($cast, \BackedEnum::class)) {
                /** @var class-string<\BackedEnum> $cast */
                $cases = $cast::cases();
                $attrs[$col] = $cases ? ($cases[array_rand($cases)]->value ?? null) : null;
                continue;
            }

            // Lunar value-object casts (need correct value types).
            if (is_string($cast) && str_contains($cast, 'TaxBreakdown')) {
                $attrs[$col] = new \Lunar\Base\ValueObjects\Cart\TaxBreakdown();
                continue;
            }
            if (is_string($cast) && str_contains($cast, 'ShippingBreakdown')) {
                $attrs[$col] = new \Lunar\Base\ValueObjects\Cart\ShippingBreakdown();
                continue;
            }
            if (is_string($cast) && str_contains($cast, 'DiscountBreakdown')) {
                $attrs[$col] = collect();
                continue;
            }

            // Eloquent AsArrayObject (Lunar uses this for translated JSON fields like `name`).
            if (is_string($cast) && str_contains($cast, 'AsArrayObject')) {
                $attrs[$col] = $this->isLikelyLocaleMapColumn($col)
                    ? $this->buildLocaleMap($this->baseStringForColumn($col))
                    : ['seeded' => true];
                continue;
            }

            if ($cast === 'boolean') {
                $attrs[$col] = fake()->boolean();
                continue;
            }
            if ($cast === 'integer') {
                $attrs[$col] = fake()->numberBetween(0, 500);
                continue;
            }
            if (is_string($cast) && str_starts_with($cast, 'decimal:')) {
                $attrs[$col] = fake()->randomFloat(2, 0, 5000);
                continue;
            }
            if ($cast === 'datetime' || $cast === 'date') {
                $attrs[$col] = now();
                continue;
            }
            if (
                $cast === 'array'
                || $cast === 'json'
                || $cast === 'collection'
                || (is_string($cast) && str_contains($cast, 'AsCollection'))
            ) {
                // For translated/locale-map fields, seed a full locale map.
                $attrs[$col] = $this->isLikelyLocaleMapColumn($col)
                    ? $this->buildLocaleMap($this->baseStringForColumn($col))
                    : ['seeded' => true];
                continue;
            }

            // Common naming patterns.
            if (in_array($col, ['name', 'title', 'label'], true)) {
                $attrs[$col] = fake()->words(3, true);
                continue;
            }
            if (Str::contains($col, 'description') || $col === 'content') {
                $attrs[$col] = fake()->sentence();
                continue;
            }
            if ($col === 'email') {
                $attrs[$col] = fake()->safeEmail();
                continue;
            }
            if ($col === 'slug' || $col === 'handle' || $col === 'code') {
                $attrs[$col] = Str::lower(Str::random(10));
                continue;
            }
            if ($col === 'serial_number' || $col === 'license_key' || Str::endsWith($col, '_key')) {
                $attrs[$col] = Str::upper(Str::random(20));
                continue;
            }
            if (Str::contains($col, 'token') || Str::contains($col, 'hash')) {
                $attrs[$col] = Str::random(32);
                continue;
            }
            if (Str::contains($col, 'ip')) {
                $attrs[$col] = fake()->ipv4();
                continue;
            }
            if (Str::contains($col, 'user_agent')) {
                $attrs[$col] = fake()->userAgent();
                continue;
            }
            if (Str::endsWith($col, '_at') || Str::contains($col, 'date')) {
                $attrs[$col] = now();
                continue;
            }

            // Status/type constants (best-effort).
            if ($col === 'status' || $col === 'type') {
                $attrs[$col] = $this->pickConstantValue($modelClass, strtoupper($col).'_') ?? 'active';
                continue;
            }

            // Default scalar.
            if (Str::contains($col, ['price', 'amount', 'total', 'quantity', 'count', 'rate', 'score', 'rank'])) {
                $attrs[$col] = fake()->numberBetween(0, 500);
                continue;
            }
            if (Str::contains($col, ['meta', 'metadata', 'config', 'settings', 'payload', 'conditions', 'data'])) {
                $attrs[$col] = json_encode(['seeded' => true], JSON_THROW_ON_ERROR);
                continue;
            }

            $attrs[$col] = fake()->word();
        }

        // Create through Eloquent so casts/mutators run.
        $model->forceFill($attrs);
        $model->save();
    }

    /**
     * Backfill foreign keys for existing rows where FK is null.
     *
     * @param class-string<Model> $modelClass
     */
    protected function backfillForeignKeys(string $modelClass): void
    {
        $model = new $modelClass();
        $table = $model->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $cols = $this->columnsForTable($table);
        $fkCols = array_keys($this->foreignKeysForTable($table));

        if (empty($fkCols)) {
            return;
        }

        $keyName = $model->getKeyName();
        if (!isset($cols[$keyName])) {
            return;
        }

        // Keep this pass cheap: avoid scanning very large tables.
        try {
            $sample = $modelClass::query()->select($keyName)->limit(1001)->get()->count();
            if ($sample > 1000) {
                return;
            }
        } catch (\Throwable) {
            // ignore
        }

        $createdCol = $model->getCreatedAtColumn();
        $updatedCol = $model->getUpdatedAtColumn();
        $usesEloquentTimestamps = $model->usesTimestamps()
            && isset($cols[$createdCol])
            && isset($cols[$updatedCol]);

        $modelClass::query()
            ->select(array_merge([$keyName], $fkCols))
            ->orderBy($keyName)
            ->chunkById(200, function ($rows) use ($fkCols, $modelClass, $table, $usesEloquentTimestamps) {
                foreach ($rows as $row) {
                    $changed = false;

                    foreach ($fkCols as $fk) {
                        if (!is_null($row->{$fk})) {
                            continue;
                        }

                        $val = $this->resolveForeignKeyValue($modelClass, $table, $fk);
                        if ($val === null) {
                            continue;
                        }

                        $row->{$fk} = $val;
                        $changed = true;
                    }

                    if ($changed) {
                        try {
                            if (!$usesEloquentTimestamps) {
                                $row->timestamps = false;
                            }
                            $row->saveQuietly();
                            $this->flushIdCache($modelClass);
                        } catch (\Throwable $e) {
                            $this->command?->warn("  ⚠️ FK backfill failed for {$modelClass}: {$e->getMessage()}");
                        }
                    }
                }
            }, $keyName);
    }

    protected function resolveForeignKeyValue(string $modelClass, string $table, string $column): ?int
    {
        // Prefer actual FK constraints when available.
        $constraints = $this->foreignKeysForTable($table);
        $constraint = $constraints[$column] ?? null;
        if ($constraint) {
            $refTable = $constraint['table'];
            $refColumn = $constraint['column'] ?: 'id';

            // Ensure the referenced table has data (best-effort).
            $refModel = $this->tableToModel[$refTable] ?? null;
            if ($refModel) {
                $this->ensureModelRows($refModel);
                try {
                    $refKey = (new $refModel())->getKeyName();
                    if ($refColumn === $refKey) {
                        return $this->randomId($refModel);
                    }
                } catch (\Throwable) {
                    // fall through
                }
            } else {
                $fallbackModel = $this->foreignKeyMap[$column] ?? null;
                if ($fallbackModel) {
                    $this->ensureModelRows($fallbackModel);
                    try {
                        $fallbackTable = (new $fallbackModel())->getTable();
                        $fallbackKey = (new $fallbackModel())->getKeyName();
                        if ($fallbackTable === $refTable && $refColumn === $fallbackKey) {
                            return $this->randomId($fallbackModel);
                        }
                    } catch (\Throwable) {
                        // fall through
                    }
                }
            }

            $val = DB::table($refTable)->inRandomOrder()->value($refColumn);
            return is_null($val) ? null : (int) $val;
        }

        $mapped = $this->foreignKeyMap[$column] ?? null;
        if ($mapped) {
            $this->ensureModelRows($mapped);
            return $this->randomId($mapped);
        }

        // Guess by convention (e.g. fraud_policy_id => App\Models\FraudPolicy).
        $base = Str::studly(Str::replaceLast('_id', '', $column));
        $guesses = [
            "App\\Models\\{$base}",
            "Lunar\\Models\\{$base}",
            "Lunar\\Admin\\Models\\{$base}",
        ];

        // Special-case media.
        if ($column === 'media_id' || $base === 'Media') {
            $guesses[] = \Spatie\MediaLibrary\MediaCollections\Models\Media::class;
        }

        foreach ($guesses as $guess) {
            if (class_exists($guess) && is_subclass_of($guess, Model::class)) {
                $this->ensureModelRows($guess);
                return $this->randomId($guess);
            }
        }

        return null;
    }

    /**
     * Seed Eloquent relations for a model class (best-effort).
     *
     * @param class-string<Model> $modelClass
     */
    protected function seedRelations(string $modelClass): void
    {
        $table = (new $modelClass())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $methods = $this->relationMethodNames($modelClass);
        if (empty($methods)) {
            return;
        }

        $defaultLocale = Language::getDefault()?->code ?? (config('app.locale') ?: 'en');

        $keyName = (new $modelClass())->getKeyName();
        $parents = $modelClass::query()->orderBy($keyName)->limit(3)->get();
        foreach ($parents as $parent) {
            foreach ($methods as $method) {
                try {
                    $relation = $parent->{$method}();
                } catch (\Throwable) {
                    continue;
                }

                if (!$relation instanceof Relation) {
                    continue;
                }

                // Skip belongsTo: handled via *_id backfill.
                if ($relation instanceof BelongsTo) {
                    continue;
                }

                // BelongsToMany / MorphToMany
                if ($relation instanceof BelongsToMany) {
                    $this->seedBelongsToManyRelation($relation, $defaultLocale);
                    continue;
                }

                // MorphOne/MorphMany
                if ($relation instanceof MorphOneOrMany) {
                    $this->seedMorphOneOrManyRelation($parent, $relation);
                    continue;
                }

                // HasOne/HasMany
                if ($relation instanceof HasOneOrMany) {
                    $this->seedHasOneOrManyRelation($parent, $relation);
                    continue;
                }
            }
        }
    }

    /**
     * @param class-string<Model> $modelClass
     * @return array<int, string>
     */
    protected function relationMethodNames(string $modelClass): array
    {
        try {
            $ref = new \ReflectionClass($modelClass);
        } catch (\Throwable) {
            return [];
        }

        $ignore = [
            'boot',
            'booted',
            'initialize',
            'casts',
            'getTable',
            'getKeyName',
            'getRouteKeyName',
        ];

        $methods = [];
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->isStatic()) {
                continue;
            }
            $declaring = $m->getDeclaringClass()->getName();
            // Include inherited relations from Lunar models (e.g. App\Models\Product extends Lunar\Models\Product),
            // but exclude Laravel's internal relationship-builder methods.
            if (
                !str_starts_with($declaring, 'App\\Models\\')
                && !str_starts_with($declaring, 'Lunar\\Models\\')
                && !str_starts_with($declaring, 'Lunar\\Admin\\Models\\')
            ) {
                continue;
            }
            if (in_array($m->getName(), $ignore, true)) {
                continue;
            }
            if (str_starts_with($m->getName(), 'scope')) {
                continue;
            }
            if ($m->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Only consider methods that *look like* Eloquent relations.
            $rt = $m->getReturnType();
            if ($rt instanceof \ReflectionNamedType && !$rt->isBuiltin()) {
                $rtName = ltrim($rt->getName(), '\\');
                if (class_exists($rtName) && is_subclass_of($rtName, Relation::class)) {
                    $methods[] = $m->getName();
                    continue;
                }
            }

            $doc = $m->getDocComment() ?: '';
            if (str_contains($doc, 'Illuminate\\Database\\Eloquent\\Relations\\') || str_contains($doc, '\\Illuminate\\Database\\Eloquent\\Relations\\')) {
                $methods[] = $m->getName();
                continue;
            }
        }

        return $methods;
    }

    protected function seedBelongsToManyRelation(BelongsToMany $relation, string $defaultLocale): void
    {
        // Already linked? good.
        try {
            if ($relation->limit(1)->exists()) {
                return;
            }
        } catch (\Throwable) {
            // If exists() fails for some reason, continue best-effort.
        }

        $relatedClass = $relation->getRelated()::class;
        $this->ensureModelRows($relatedClass);
        $relatedId = $this->randomId($relatedClass);
        if ($relatedId === null) {
            return;
        }

        // Try minimal attach first.
        try {
            $relation->syncWithoutDetaching([$relatedId]);
            return;
        } catch (\Throwable) {
            // Retry with pivot defaults.
        }

        $pivotTable = $relation->getTable();
        if (!Schema::hasTable($pivotTable)) {
            return;
        }

        $pivotCols = Schema::getColumnListing($pivotTable);
        $ignore = [
            $relation->getForeignPivotKeyName(),
            $relation->getRelatedPivotKeyName(),
            'created_at',
            'updated_at',
        ];

        $pivotData = [];
        foreach ($pivotCols as $col) {
            if (in_array($col, $ignore, true)) {
                continue;
            }

            if (str_ends_with($col, '_id')) {
                $pivotData[$col] = $this->resolveForeignKeyValue($relation->getParent()::class, $pivotTable, $col);
                continue;
            }

            if ($col === 'locale') {
                $pivotData[$col] = $defaultLocale;
                continue;
            }

            if (str_ends_with($col, '_at')) {
                $pivotData[$col] = now();
                continue;
            }

            if (str_contains($col, 'position')) {
                $pivotData[$col] = 1;
                continue;
            }

            if (str_contains($col, 'priority') || str_contains($col, 'order') || str_contains($col, 'count')) {
                $pivotData[$col] = 0;
                continue;
            }

            if (str_starts_with($col, 'is_') || str_contains($col, 'enabled') || str_contains($col, 'active')) {
                $pivotData[$col] = true;
                continue;
            }

            if (str_contains($col, 'meta') || str_contains($col, 'rules') || str_contains($col, 'conditions') || str_contains($col, 'data')) {
                $pivotData[$col] = [];
                continue;
            }

            if (str_contains($col, 'type')) {
                $pivotData[$col] = 'manual';
                continue;
            }

            $pivotData[$col] = 'seed';
        }

        try {
            $relation->syncWithoutDetaching([
                $relatedId => $pivotData,
            ]);
        } catch (\Throwable) {
            // Best-effort only.
        }
    }

    protected function seedHasOneOrManyRelation(Model $parent, HasOneOrMany $relation): void
    {
        try {
            if ($relation->limit(1)->exists()) {
                return;
            }
        } catch (\Throwable) {
            // ignore
        }

        $relatedClass = $relation->getRelated()::class;
        $foreignKey = $relation->getForeignKeyName();
        $localKey = $relation->getLocalKeyName();

        $this->createRelatedRow($relatedClass, [
            $foreignKey => $parent->{$localKey},
        ]);
    }

    protected function seedMorphOneOrManyRelation(Model $parent, MorphOneOrMany $relation): void
    {
        try {
            if ($relation->limit(1)->exists()) {
                return;
            }
        } catch (\Throwable) {
            // ignore
        }

        $relatedClass = $relation->getRelated()::class;
        $morphType = $relation->getMorphType();
        $foreignKey = $relation->getForeignKeyName();

        $this->createRelatedRow($relatedClass, [
            $morphType => $parent->getMorphClass(),
            $foreignKey => $parent->getKey(),
        ]);
    }

    /**
     * Create a related row using factory if available, else fallback generator.
     *
     * @param class-string<Model> $modelClass
     * @param array<string,mixed> $overrides
     */
    protected function createRelatedRow(string $modelClass, array $overrides): void
    {
        /** @var Model $probe */
        $probe = new $modelClass();
        $table = $probe->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        // Some relations may reference non-existent FK columns (schema drift).
        // Filter overrides to actual table columns to avoid crashing the seeder.
        $cols = $this->columnsForTable($table);
        $overrides = array_intersect_key($overrides, $cols);

        $factoryClass = 'Database\\Factories\\'.class_basename($modelClass).'Factory';
        if ($this->shouldUseModelFactory($modelClass) && class_exists($factoryClass) && method_exists($modelClass, 'factory')) {
            try {
                $modelClass::factory()->create($overrides);
                $this->flushIdCache($modelClass);
                return;
            } catch (\Throwable) {
                // fall back
            }
        }

        $last = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $this->createFallbackRowWithOverrides($modelClass, $overrides);
                $this->flushIdCache($modelClass);
                return;
            } catch (\Throwable $e) {
                $last = $e;
            }
        }

        $this->command?->warn('  ⚠️ Related row create failed for '.$modelClass.': '.($last?->getMessage() ?? 'unknown error'));
    }

    /**
     * @param class-string<Model> $modelClass
     * @param array<string,mixed> $overrides
     */
    protected function createFallbackRowWithOverrides(string $modelClass, array $overrides): void
    {
        /** @var Model $model */
        $model = new $modelClass();
        $table = $model->getTable();
        $cols = $this->columnsForTable($table);
        $meta = $this->columnMetaForTable($table);
        $casts = $model->getCasts();

        $attrs = $overrides;

        // Avoid inserting timestamps into tables that don't support them.
        $createdCol = $model->getCreatedAtColumn();
        $updatedCol = $model->getUpdatedAtColumn();
        $usesEloquentTimestamps = $model->usesTimestamps()
            && isset($cols[$createdCol])
            && isset($cols[$updatedCol]);
        if (!$usesEloquentTimestamps) {
            $model->timestamps = false;
        }

        foreach (array_keys($cols) as $col) {
            if ($col === $model->getKeyName() || $col === 'deleted_at') {
                continue;
            }
            if ($usesEloquentTimestamps && ($col === $createdCol || $col === $updatedCol)) {
                continue;
            }
            if (array_key_exists($col, $attrs)) {
                continue;
            }

            $hasMeta = array_key_exists($col, $meta);
            $nullable = $hasMeta ? ($meta[$col]['nullable'] ?? true) : false;
            $hasDefault = $hasMeta ? ($meta[$col]['has_default'] ?? false) : false;

            if ($hasMeta && $hasDefault) {
                continue;
            }
            if ($hasMeta && $nullable) {
                continue;
            }

            if (Str::endsWith($col, '_id')) {
                $attrs[$col] = $this->resolveForeignKeyValue($modelClass, $table, $col);
                continue;
            }

            if (Str::endsWith($col, '_type')) {
                $base = Str::beforeLast($col, '_type');
                $idCol = $base.'_id';

                if (isset($cols[$idCol]) && $this->isLikelyMorphBase($base)) {
                    $attrs[$col] = \App\Models\Product::class;
                    $attrs[$idCol] = $attrs[$idCol]
                        ?? (\App\Models\Product::query()->inRandomOrder()->value('id') ?? null);
                    continue;
                }
            }

            $enumValues = $this->enumValuesForColumn($table, $col);
            if (is_array($enumValues) && !empty($enumValues)) {
                $attrs[$col] = $enumValues[array_rand($enumValues)];
                continue;
            }

            $cast = $casts[$col] ?? null;
            if (is_string($cast) && enum_exists($cast) && is_subclass_of($cast, \BackedEnum::class)) {
                /** @var class-string<\BackedEnum> $cast */
                $cases = $cast::cases();
                $attrs[$col] = $cases ? ($cases[array_rand($cases)]->value ?? null) : null;
                continue;
            }

            if (is_string($cast) && str_contains($cast, 'TaxBreakdown')) {
                $attrs[$col] = new \Lunar\Base\ValueObjects\Cart\TaxBreakdown();
                continue;
            }
            if (is_string($cast) && str_contains($cast, 'ShippingBreakdown')) {
                $attrs[$col] = new \Lunar\Base\ValueObjects\Cart\ShippingBreakdown();
                continue;
            }
            if (is_string($cast) && str_contains($cast, 'DiscountBreakdown')) {
                $attrs[$col] = collect();
                continue;
            }

            if (is_string($cast) && str_contains($cast, 'AsArrayObject')) {
                $attrs[$col] = $this->isLikelyLocaleMapColumn($col)
                    ? $this->buildLocaleMap($this->baseStringForColumn($col))
                    : ['seeded' => true];
                continue;
            }
            if ($cast === 'boolean') {
                $attrs[$col] = fake()->boolean();
                continue;
            }
            if ($cast === 'integer') {
                $attrs[$col] = fake()->numberBetween(0, 500);
                continue;
            }
            if (is_string($cast) && str_starts_with($cast, 'decimal:')) {
                $attrs[$col] = fake()->randomFloat(2, 0, 5000);
                continue;
            }
            if ($cast === 'datetime' || $cast === 'date') {
                $attrs[$col] = now();
                continue;
            }
            if (
                $cast === 'array'
                || $cast === 'json'
                || $cast === 'collection'
                || (is_string($cast) && str_contains($cast, 'AsCollection'))
            ) {
                $attrs[$col] = $this->isLikelyLocaleMapColumn($col)
                    ? $this->buildLocaleMap($this->baseStringForColumn($col))
                    : ['seeded' => true];
                continue;
            }

            if (in_array($col, ['name', 'title', 'label'], true)) {
                $attrs[$col] = fake()->words(3, true);
                continue;
            }
            if (Str::contains($col, 'description') || $col === 'content') {
                $attrs[$col] = fake()->sentence();
                continue;
            }
            if ($col === 'email') {
                $attrs[$col] = fake()->safeEmail();
                continue;
            }
            if ($col === 'slug' || $col === 'handle' || $col === 'code') {
                $attrs[$col] = Str::lower(Str::random(10));
                continue;
            }
            if ($col === 'serial_number' || $col === 'license_key' || Str::endsWith($col, '_key')) {
                $attrs[$col] = Str::upper(Str::random(20));
                continue;
            }
            if (Str::contains($col, 'token') || Str::contains($col, 'hash')) {
                $attrs[$col] = Str::random(32);
                continue;
            }
            if (Str::contains($col, 'ip')) {
                $attrs[$col] = fake()->ipv4();
                continue;
            }
            if (Str::contains($col, 'user_agent')) {
                $attrs[$col] = fake()->userAgent();
                continue;
            }
            if (Str::endsWith($col, '_at') || Str::contains($col, 'date')) {
                $attrs[$col] = now();
                continue;
            }

            if ($col === 'status' || $col === 'type') {
                $attrs[$col] = $this->pickConstantValue($modelClass, strtoupper($col).'_') ?? 'active';
                continue;
            }

            if (Str::contains($col, ['price', 'amount', 'total', 'quantity', 'count', 'rate', 'score', 'rank'])) {
                $attrs[$col] = fake()->numberBetween(0, 500);
                continue;
            }
            if (Str::contains($col, ['meta', 'metadata', 'config', 'settings', 'payload', 'conditions', 'data'])) {
                $attrs[$col] = json_encode(['seeded' => true], JSON_THROW_ON_ERROR);
                continue;
            }

            $attrs[$col] = fake()->word();
        }

        $model->forceFill($attrs);
        $model->save();
    }

    /**
     * @param class-string<Model> $modelClass
     */
    protected function randomId(string $modelClass): ?int
    {
        $ids = $this->idCache[$modelClass] ?? null;
        if (!$ids) {
            $ids = $modelClass::query()->pluck((new $modelClass())->getKeyName())->filter()->map(fn ($v) => (int) $v)->values()->all();
            $this->idCache[$modelClass] = $ids;
        }

        if (empty($ids)) {
            return null;
        }

        return $ids[array_rand($ids)];
    }

    /**
     * @return array<string,true>
     */
    protected function columnsForTable(string $table): array
    {
        if (isset($this->tableColumns[$table])) {
            return $this->tableColumns[$table];
        }

        if (!Schema::hasTable($table)) {
            return $this->tableColumns[$table] = [];
        }

        return $this->tableColumns[$table] = array_fill_keys(Schema::getColumnListing($table), true);
    }

    /**
     * @return array<string, array{nullable: bool, has_default: bool}>
     */
    protected function columnMetaForTable(string $table): array
    {
        if (isset($this->columnMeta[$table])) {
            return $this->columnMeta[$table];
        }

        $out = [];

        if (!Schema::hasTable($table)) {
            return $this->columnMeta[$table] = $out;
        }

        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $escaped = str_replace('"', '""', $table);
                $rows = DB::select("PRAGMA table_info(\"{$escaped}\")");
                foreach ($rows as $row) {
                    $name = (string) ($row->name ?? '');
                    if ($name === '') {
                        continue;
                    }
                    $notNull = (int) ($row->notnull ?? 0) === 1;
                    $defaultRaw = $row->dflt_value ?? null;
                    $hasDefault = $defaultRaw !== null && strtoupper((string) $defaultRaw) !== 'NULL';

                    $out[$name] = [
                        'nullable' => !$notNull,
                        'has_default' => $hasDefault,
                    ];
                }

                return $this->columnMeta[$table] = $out;
            }

            if ($driver === 'mysql' || $driver === 'mariadb') {
                $schema = DB::connection()->getDatabaseName();
                $rows = DB::select(
                    'select COLUMN_NAME as name, IS_NULLABLE as is_nullable, COLUMN_DEFAULT as default_value from information_schema.columns where table_schema = ? and table_name = ?',
                    [$schema, $table]
                );

                foreach ($rows as $row) {
                    $name = (string) ($row->name ?? '');
                    if ($name === '') {
                        continue;
                    }
                    $nullable = strtoupper((string) ($row->is_nullable ?? 'YES')) === 'YES';
                    $hasDefault = ($row->default_value ?? null) !== null;

                    $out[$name] = [
                        'nullable' => $nullable,
                        'has_default' => $hasDefault,
                    ];
                }

                return $this->columnMeta[$table] = $out;
            }
        } catch (\Throwable) {
            // Ignore metadata failures; we'll fall back to "unknown".
        }

        // Default fallback: unknown nullable/default (assume nullable/no default to be safe).
        return $this->columnMeta[$table] = $out;
    }

    /**
     * @return array<string, array{table: string, column: string}>
     */
    protected function foreignKeysForTable(string $table): array
    {
        if (isset($this->foreignKeyConstraints[$table])) {
            return $this->foreignKeyConstraints[$table];
        }

        $out = [];

        if (!Schema::hasTable($table)) {
            return $this->foreignKeyConstraints[$table] = $out;
        }

        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $escaped = str_replace('"', '""', $table);
                $rows = DB::select("PRAGMA foreign_key_list(\"{$escaped}\")");
                foreach ($rows as $row) {
                    $from = (string) ($row->from ?? '');
                    $toTable = (string) ($row->table ?? '');
                    $toColumn = (string) ($row->to ?? 'id');
                    if ($from === '' || $toTable === '') {
                        continue;
                    }
                    $out[$from] = ['table' => $toTable, 'column' => $toColumn ?: 'id'];
                }

                return $this->foreignKeyConstraints[$table] = $out;
            }

            if ($driver === 'mysql' || $driver === 'mariadb') {
                $schema = DB::connection()->getDatabaseName();
                $rows = DB::select(
                    'select COLUMN_NAME as col, REFERENCED_TABLE_NAME as ref_table, REFERENCED_COLUMN_NAME as ref_col from information_schema.KEY_COLUMN_USAGE where TABLE_SCHEMA = ? and TABLE_NAME = ? and REFERENCED_TABLE_NAME is not null',
                    [$schema, $table]
                );
                foreach ($rows as $row) {
                    $from = (string) ($row->col ?? '');
                    $toTable = (string) ($row->ref_table ?? '');
                    $toColumn = (string) ($row->ref_col ?? 'id');
                    if ($from === '' || $toTable === '') {
                        continue;
                    }
                    $out[$from] = ['table' => $toTable, 'column' => $toColumn ?: 'id'];
                }

                return $this->foreignKeyConstraints[$table] = $out;
            }
        } catch (\Throwable) {
            // Ignore FK metadata failures.
        }

        return $this->foreignKeyConstraints[$table] = $out;
    }

    /**
     * Best-effort enum value extraction from schema (sqlite/mysql).
     *
     * @return array<int, string>|null
     */
    protected function enumValuesForColumn(string $table, string $column): ?array
    {
        $cacheKey = "{$table}.{$column}";
        if (array_key_exists($cacheKey, $this->enumCache)) {
            return $this->enumCache[$cacheKey];
        }

        $driver = DB::connection()->getDriverName();

        // SQLite: parse CHECK constraint from CREATE TABLE sql.
        if ($driver === 'sqlite') {
            $sql = $this->sqliteCreateSqlForTable($table);
            if (!is_string($sql) || $sql === '') {
                return $this->enumCache[$cacheKey] = null;
            }

            $col = preg_quote($column, '/');
            $patterns = [
                // "col" ... CHECK ("col" IN ('a','b'))
                '/"'.$col.'"[^,]*?check\\s*\\(\\s*"'.$col.'"\\s+in\\s*\\(([^\\)]*)\\)\\s*\\)/is',
                // col ... CHECK (col IN ('a','b'))
                '/\\b'.$col.'\\b[^,]*?check\\s*\\(\\s*\\b'.$col.'\\b\\s+in\\s*\\(([^\\)]*)\\)\\s*\\)/is',
            ];

            foreach ($patterns as $pattern) {
                if (!preg_match($pattern, $sql, $m)) {
                    continue;
                }

                $inside = (string) ($m[1] ?? '');
                $values = [];
                if (preg_match_all("/'((?:''|[^'])*)'/", $inside, $mm)) {
                    foreach ($mm[1] as $raw) {
                        $values[] = str_replace("''", "'", (string) $raw);
                    }
                }

                $values = array_values(array_unique(array_filter($values, fn ($v) => $v !== '')));
                return $this->enumCache[$cacheKey] = ($values ?: null);
            }

            return $this->enumCache[$cacheKey] = null;
        }

        // MySQL: parse enum(...) from information_schema.
        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                $schema = DB::connection()->getDatabaseName();
                $row = DB::selectOne(
                    'select COLUMN_TYPE as column_type from information_schema.columns where table_schema = ? and table_name = ? and column_name = ? limit 1',
                    [$schema, $table, $column]
                );
                $type = (string) ($row->column_type ?? '');
                if (str_starts_with($type, 'enum(')) {
                    $values = [];
                    if (preg_match_all("/'((?:\\\\'|[^'])*)'/", $type, $mm)) {
                        foreach ($mm[1] as $raw) {
                            $values[] = str_replace("\\'", "'", (string) $raw);
                        }
                    }
                    $values = array_values(array_unique(array_filter($values, fn ($v) => $v !== '')));
                    return $this->enumCache[$cacheKey] = ($values ?: null);
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        return $this->enumCache[$cacheKey] = null;
    }

    protected function sqliteCreateSqlForTable(string $table): ?string
    {
        if (array_key_exists($table, $this->sqliteCreateSql)) {
            return $this->sqliteCreateSql[$table];
        }

        try {
            $row = DB::selectOne("select sql from sqlite_master where type = 'table' and name = ? limit 1", [$table]);
            $sql = $row?->sql ?? null;
            return $this->sqliteCreateSql[$table] = (is_string($sql) ? $sql : null);
        } catch (\Throwable) {
            return $this->sqliteCreateSql[$table] = null;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function locales(): array
    {
        if (is_array($this->cachedLocales)) {
            return $this->cachedLocales;
        }

        try {
            $table = (new \Lunar\Models\Language())->getTable();
            if (Schema::hasTable($table) && \Lunar\Models\Language::query()->exists()) {
                $codes = \Lunar\Models\Language::query()->orderBy('id')->pluck('code')->filter()->values()->all();
                if (!empty($codes)) {
                    return $this->cachedLocales = $codes;
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return $this->cachedLocales = [config('app.locale') ?: 'en'];
    }

    /**
     * Only use `$modelClass::factory()` for app models.
     *
     * Lunar core models ship their own factories, which can create "random" extra records
     * (e.g. extra Languages via `UrlFactory`), so we avoid using them here.
     *
     * @param class-string<Model> $modelClass
     */
    protected function shouldUseModelFactory(string $modelClass): bool
    {
        return str_starts_with($modelClass, 'App\\Models\\');
    }

    protected function buildLocaleMap(string $base): array
    {
        $out = [];
        foreach ($this->locales() as $locale) {
            $out[$locale] = $base;
        }
        return $out;
    }

    protected function baseStringForColumn(string $column): string
    {
        $col = Str::lower($column);
        if (str_contains($col, 'description') || $col === 'content' || $col === 'caption') {
            return fake()->sentence();
        }
        return fake()->words(3, true);
    }

    protected function isLikelyLocaleMapColumn(string $column): bool
    {
        $col = Str::lower($column);

        return in_array($col, [
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

    protected function isLikelyMorphBase(string $base): bool
    {
        if (Str::endsWith($base, 'able')) {
            return true;
        }

        return in_array($base, ['model', 'purchasable', 'subject', 'owner'], true);
    }

    /**
     * Pick a value from class constants with a given prefix.
     */
    protected function pickConstantValue(string $class, string $prefix): ?string
    {
        try {
            $ref = new \ReflectionClass($class);
            $constants = $ref->getConstants();
            $candidates = [];
            foreach ($constants as $name => $value) {
                if (!is_string($value)) {
                    continue;
                }
                if (str_starts_with($name, $prefix)) {
                    $candidates[] = $value;
                }
            }
            return $candidates ? $candidates[array_rand($candidates)] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param class-string<Model> $modelClass
     */
    protected function flushIdCache(string $modelClass): void
    {
        unset($this->idCache[$modelClass]);
    }
}

