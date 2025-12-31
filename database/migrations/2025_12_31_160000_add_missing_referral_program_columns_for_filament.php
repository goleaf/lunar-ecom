<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_programs', function (Blueprint $table) {
            // Keep migrations compatible with SQLite (tests) by only adding missing columns.

            if (!Schema::hasColumn('referral_programs', 'status')) {
                $table->string('status')->default('draft')->index();
            }

            if (!Schema::hasColumn('referral_programs', 'start_at')) {
                $table->timestamp('start_at')->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'end_at')) {
                $table->timestamp('end_at')->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'channel_ids')) {
                $table->json('channel_ids')->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'currency_scope')) {
                $table->string('currency_scope')->default('all');
            }

            if (!Schema::hasColumn('referral_programs', 'currency_ids')) {
                $table->json('currency_ids')->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'audience_scope')) {
                $table->string('audience_scope')->default('all');
            }

            if (!Schema::hasColumn('referral_programs', 'audience_user_ids')) {
                $table->json('audience_user_ids')->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'audience_group_ids')) {
                $table->json('audience_group_ids')->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'terms_url')) {
                $table->string('terms_url')->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'last_click_wins')) {
                $table->boolean('last_click_wins')->default(true);
            }

            if (!Schema::hasColumn('referral_programs', 'attribution_ttl_days')) {
                $table->integer('attribution_ttl_days')->default(7);
            }

            if (!Schema::hasColumn('referral_programs', 'default_stacking_mode')) {
                $table->string('default_stacking_mode')->default('exclusive');
            }

            if (!Schema::hasColumn('referral_programs', 'max_total_discount_percent')) {
                $table->decimal('max_total_discount_percent', 5, 2)->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'max_total_discount_amount')) {
                $table->decimal('max_total_discount_amount', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('referral_programs', 'apply_before_tax')) {
                $table->boolean('apply_before_tax')->default(true);
            }

            if (!Schema::hasColumn('referral_programs', 'shipping_discount_stacks')) {
                $table->boolean('shipping_discount_stacks')->default(false);
            }
        });
    }

    public function down(): void
    {
        // Intentionally left minimal: dropping columns is not required for test runs
        // and can be problematic on SQLite depending on configuration.
    }
};

