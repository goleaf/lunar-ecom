<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained('referral_programs')->onDelete('cascade');
            $table->foreignId('referral_event_id')->nullable()->constrained('referral_events')->onDelete('set null');
            
            // Reward recipient
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('lunar_customers')->onDelete('cascade');
            
            // Reward details
            $table->string('reward_type'); // discount_code, credit, percentage, fixed_amount
            $table->decimal('reward_value', 15, 2);
            $table->foreignId('currency_id')->nullable()->constrained('lunar_currencies')->onDelete('set null');
            
            // Reward delivery
            $table->string('status')->default('pending'); // pending, issued, redeemed, expired, cancelled
            $table->string('delivery_method')->default('automatic'); // automatic, manual, email
            
            // Discount code (if reward_type is discount_code)
            $table->foreignId('discount_id')->nullable()->constrained('lunar_discounts')->onDelete('set null');
            $table->string('discount_code')->nullable();
            
            // Validity
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            
            // Usage tracking
            $table->integer('times_used')->default(0);
            $table->integer('max_uses')->default(1);
            
            // Related order (if redeemed)
            $table->foreignId('redeemed_order_id')->nullable()->constrained('lunar_orders')->onDelete('set null');
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('discount_id');
            $table->index('referral_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};

