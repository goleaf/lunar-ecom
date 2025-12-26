<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_reward_issuances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_rule_id')->constrained('referral_rules')->onDelete('cascade');
            $table->foreignId('referral_attribution_id')->constrained('referral_attributions')->onDelete('cascade');
            $table->foreignId('referee_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('lunar_orders')->onDelete('set null');
            $table->string('referee_reward_type')->nullable();
            $table->decimal('referee_reward_value', 10, 2)->nullable();
            $table->string('referrer_reward_type')->nullable();
            $table->decimal('referrer_reward_value', 10, 2)->nullable();
            $table->string('status')->default('pending'); // pending, issued, reversed
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['referee_user_id', 'status']);
            $table->index(['referrer_user_id', 'status']);
            $table->index(['referral_rule_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_reward_issuances');
    }
};


