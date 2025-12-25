<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('allow_same_ip')->default(false);
            $table->integer('max_signups_per_ip_per_day')->nullable();
            $table->integer('max_orders_per_ip_per_day')->nullable();
            $table->boolean('block_disposable_emails')->default(true);
            $table->boolean('block_same_card_fingerprint')->default(false);
            $table->boolean('require_email_verified')->default(false);
            $table->boolean('require_phone_verified')->default(false);
            $table->integer('min_account_age_days_before_reward')->default(0);
            $table->integer('manual_review_threshold')->nullable(); // Risk score threshold
            $table->json('custom_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_policies');
    }
};

