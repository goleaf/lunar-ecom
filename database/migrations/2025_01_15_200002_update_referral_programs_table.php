<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_programs', function (Blueprint $table) {
            // Remove old fields that are now in ReferralRule
            $table->dropColumn([
                'referrer_rewards',
                'referee_rewards',
                'max_referrals_per_referrer',
                'max_referrals_total',
                'max_rewards_per_referrer',
                'require_referee_purchase',
                'stacking_mode',
                'stacking_rules',
            ]);
            
            // Add new fields
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft')->change();
            $table->timestamp('start_at')->nullable()->change();
            $table->timestamp('end_at')->nullable()->change();
            $table->renameColumn('starts_at', 'start_at');
            $table->renameColumn('ends_at', 'end_at');
            
            // Channel and currency scope
            $table->json('channel_ids')->nullable()->after('end_at');
            $table->enum('currency_scope', ['all', 'specific'])->default('all')->after('channel_ids');
            $table->json('currency_ids')->nullable()->after('currency_scope');
            
            // Audience scope
            $table->enum('audience_scope', ['all', 'users', 'groups'])->default('all')->after('currency_ids');
            $table->json('audience_user_ids')->nullable()->after('audience_scope');
            $table->json('audience_group_ids')->nullable()->after('audience_user_ids');
            
            // Terms and description
            $table->string('terms_url')->nullable()->after('description');
            
            // Remove old eligibility fields (replaced by audience_scope)
            $table->dropColumn(['eligible_customer_groups', 'eligible_users', 'eligible_conditions']);
        });
    }

    public function down(): void
    {
        Schema::table('referral_programs', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft')->change();
            $table->renameColumn('start_at', 'starts_at');
            $table->renameColumn('end_at', 'ends_at');
            $table->dropColumn([
                'channel_ids',
                'currency_scope',
                'currency_ids',
                'audience_scope',
                'audience_user_ids',
                'audience_group_ids',
                'terms_url',
            ]);
            
            // Restore old fields (simplified)
            $table->json('referrer_rewards')->nullable();
            $table->json('referee_rewards')->nullable();
            $table->integer('max_referrals_per_referrer')->nullable();
            $table->integer('max_referrals_total')->nullable();
            $table->integer('max_rewards_per_referrer')->nullable();
            $table->boolean('require_referee_purchase')->default(false);
            $table->string('stacking_mode')->default('non_stackable');
            $table->json('stacking_rules')->nullable();
            $table->json('eligible_customer_groups')->nullable();
            $table->json('eligible_users')->nullable();
            $table->json('eligible_conditions')->nullable();
        });
    }
};


