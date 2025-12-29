<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = $this->prefix . 'price_matrices';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $driver) {
            if (! Schema::hasColumn($tableName, 'product_variant_id')) {
                // SQLite can't reliably add foreign keys after table creation.
                if ($driver === 'sqlite') {
                    $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                } else {
                    $table->foreignId('product_variant_id')
                        ->nullable()
                        ->constrained($this->prefix . 'product_variants')
                        ->cascadeOnDelete();
                }
            }

            if (! Schema::hasColumn($tableName, 'name')) {
                $table->string('name')->nullable()->index();
            }

            if (! Schema::hasColumn($tableName, 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->index();
            }

            if (! Schema::hasColumn($tableName, 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index();
            }

            if (! Schema::hasColumn($tableName, 'allow_mix_match')) {
                $table->boolean('allow_mix_match')->default(false)->index();
            }

            if (! Schema::hasColumn($tableName, 'mix_match_variants')) {
                $table->json('mix_match_variants')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'mix_match_min_quantity')) {
                $table->integer('mix_match_min_quantity')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'min_order_quantity')) {
                $table->integer('min_order_quantity')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'max_order_quantity')) {
                $table->integer('max_order_quantity')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'approval_status')) {
                $table->string('approval_status')->default('approved')->index();
            }

            if (! Schema::hasColumn($tableName, 'approved_by')) {
                if ($driver === 'sqlite') {
                    $table->unsignedBigInteger('approved_by')->nullable()->index();
                } else {
                    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                }
            }

            if (! Schema::hasColumn($tableName, 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'approval_notes')) {
                $table->text('approval_notes')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'rules')) {
                $table->json('rules')->nullable();
            }
        });

        // Backfill expires_at from legacy ends_at when present.
        if (Schema::hasColumn($tableName, 'ends_at') && Schema::hasColumn($tableName, 'expires_at')) {
            DB::table($tableName)
                ->whereNull('expires_at')
                ->whereNotNull('ends_at')
                ->update([
                    'expires_at' => DB::raw('ends_at'),
                ]);
        }

        // Ensure approval_status is set consistently for legacy rows.
        if (Schema::hasColumn($tableName, 'requires_approval') && Schema::hasColumn($tableName, 'approval_status')) {
            DB::table($tableName)
                ->where('requires_approval', true)
                ->whereNull('approval_status')
                ->update(['approval_status' => 'pending']);
        }
    }

    public function down(): void
    {
        // Non-destructive migration: we don't drop columns in down()
        // to avoid accidental data loss in production.
    }
};

