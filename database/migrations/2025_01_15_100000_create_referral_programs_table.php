<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->text('description')->nullable();
            
            // Program settings
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Eligibility rules
            $table->json('eligible_customer_groups')->nullable(); // Array of customer group IDs
            $table->json('eligible_users')->nullable(); // Array of user IDs (specific users)
            $table->json('eligible_conditions')->nullable(); // Custom conditions (e.g., min orders, etc.)
            
            // Referrer rewards configuration
            $table->json('referrer_rewards')->nullable(); // Array of reward rules
            // Example: [
            //   {'action': 'signup', 'type': 'discount', 'value': 10, 'currency_id': 1},
            //   {'action': 'first_purchase', 'type': 'credit', 'value': 5000, 'currency_id': 1},
            //   {'action': 'repeat_purchase', 'type': 'percentage', 'value': 5, 'max_per_order': 1000}
            // ]
            
            // Referee (invited user) rewards configuration
            $table->json('referee_rewards')->nullable(); // Welcome discount for referees
            // Example: [
            //   {'type': 'discount', 'value': 15, 'coupon_code': 'WELCOME15', 'valid_days': 30}
            // ]
            
            // Limits and restrictions
            $table->integer('max_referrals_per_referrer')->nullable(); // Null = unlimited
            $table->integer('max_referrals_total')->nullable(); // Null = unlimited
            $table->integer('max_rewards_per_referrer')->nullable(); // Null = unlimited
            $table->boolean('allow_self_referral')->default(false);
            $table->boolean('require_referee_purchase')->default(false); // Require purchase before reward
            
            // Stacking rules
            $table->string('stacking_mode')->default('non_stackable'); // stackable, non_stackable, exclusive
            $table->json('stacking_rules')->nullable(); // Custom stacking rules
            
            // Validity
            $table->integer('referral_code_validity_days')->default(365); // How long referral codes are valid
            $table->integer('reward_validity_days')->nullable(); // How long rewards are valid after earning
            
            // Tracking
            $table->integer('total_referrals')->default(0);
            $table->integer('total_rewards_issued')->default(0);
            $table->decimal('total_reward_value', 15, 2)->default(0);
            
            // Metadata
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index('handle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_programs');
    }
};


