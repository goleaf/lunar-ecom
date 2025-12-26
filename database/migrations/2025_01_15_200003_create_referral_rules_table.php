<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained('referral_programs')->onDelete('cascade');
            
            // Trigger event
            $table->string('trigger_event'); // signup, first_order_paid, nth_order_paid, subscription_started, etc.
            $table->integer('nth_order')->nullable(); // For nth_order_paid trigger
            
            // Referee rewards
            $table->enum('referee_reward_type', ['coupon', 'percentage_discount', 'fixed_discount', 'free_shipping', 'store_credit'])->nullable();
            $table->decimal('referee_reward_value', 15, 2)->nullable();
            
            // Referrer rewards
            $table->enum('referrer_reward_type', ['coupon', 'store_credit', 'percentage_discount_next_order', 'fixed_amount'])->nullable();
            $table->decimal('referrer_reward_value', 15, 2)->nullable();
            
            // Eligibility
            $table->decimal('min_order_total', 15, 2)->nullable();
            $table->json('eligible_product_ids')->nullable();
            $table->json('eligible_category_ids')->nullable();
            $table->json('eligible_collection_ids')->nullable();
            
            // Limits
            $table->integer('max_redemptions_total')->nullable();
            $table->integer('max_redemptions_per_referrer')->nullable();
            $table->integer('max_redemptions_per_referee')->nullable();
            
            // Timing
            $table->integer('cooldown_days')->nullable();
            $table->integer('validation_window_days')->nullable(); // Referee must order within X days
            
            // Stacking
            $table->enum('stacking_mode', ['exclusive', 'stackable', 'best_of'])->default('exclusive');
            $table->integer('priority')->default(0);
            
            // Fraud
            $table->foreignId('fraud_policy_id')->nullable()->constrained('fraud_policies')->onDelete('set null');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['referral_program_id', 'trigger_event', 'is_active']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rules');
    }
};


