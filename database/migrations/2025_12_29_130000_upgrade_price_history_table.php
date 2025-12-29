<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = $this->prefix . 'price_history';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $driver) {
            // Columns introduced by the "advanced pricing features" version of price_history.
            // We add them non-destructively so older migrations keep working and newer services
            // (AdvancedPricingService / PriceHistory model) can safely write/read.

            if (! Schema::hasColumn($tableName, 'currency_id')) {
                if ($driver === 'sqlite') {
                    $table->unsignedBigInteger('currency_id')->nullable()->index();
                } else {
                    $table->foreignId('currency_id')
                        ->nullable()
                        ->constrained($this->prefix . 'currencies')
                        ->nullOnDelete();
                }
            }

            if (! Schema::hasColumn($tableName, 'price')) {
                $table->integer('price')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'compare_at_price')) {
                $table->integer('compare_at_price')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'channel_id')) {
                if ($driver === 'sqlite') {
                    $table->unsignedBigInteger('channel_id')->nullable()->index();
                } else {
                    $table->foreignId('channel_id')
                        ->nullable()
                        ->constrained($this->prefix . 'channels')
                        ->nullOnDelete();
                }
            }

            if (! Schema::hasColumn($tableName, 'customer_group_id')) {
                if ($driver === 'sqlite') {
                    $table->unsignedBigInteger('customer_group_id')->nullable()->index();
                } else {
                    $table->foreignId('customer_group_id')
                        ->nullable()
                        ->constrained($this->prefix . 'customer_groups')
                        ->nullOnDelete();
                }
            }

            if (! Schema::hasColumn($tableName, 'pricing_layer')) {
                $table->string('pricing_layer')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'pricing_rule_id')) {
                if ($driver === 'sqlite') {
                    $table->unsignedBigInteger('pricing_rule_id')->nullable()->index();
                } else {
                    $table->foreignId('pricing_rule_id')
                        ->nullable()
                        ->constrained($this->prefix . 'pricing_rules')
                        ->nullOnDelete();
                }
            }

            if (! Schema::hasColumn($tableName, 'change_metadata')) {
                $table->json('change_metadata')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'effective_from')) {
                $table->timestamp('effective_from')->nullable()->index();
            }

            if (! Schema::hasColumn($tableName, 'effective_to')) {
                $table->timestamp('effective_to')->nullable()->index();
            }
        });

        // Best-effort backfills to keep legacy history usable in new UIs.
        if (Schema::hasColumn($tableName, 'changed_at') && Schema::hasColumn($tableName, 'effective_from')) {
            DB::table($tableName)
                ->whereNull('effective_from')
                ->whereNotNull('changed_at')
                ->update(['effective_from' => DB::raw('changed_at')]);
        }

        if (
            Schema::hasTable($this->prefix . 'currencies')
            && Schema::hasColumn($tableName, 'currency_code')
            && Schema::hasColumn($tableName, 'currency_id')
        ) {
            $currencies = DB::table($this->prefix . 'currencies')->select(['id', 'code'])->get();
            foreach ($currencies as $currency) {
                DB::table($tableName)
                    ->whereNull('currency_id')
                    ->where('currency_code', $currency->code)
                    ->update(['currency_id' => $currency->id]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive migration: we don't drop columns in down()
        // to avoid accidental data loss in production.
    }
};

