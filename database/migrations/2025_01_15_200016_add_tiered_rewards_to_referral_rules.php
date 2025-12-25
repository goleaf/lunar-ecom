<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_rules', function (Blueprint $table) {
            $table->json('tiered_rewards')->nullable()->after('referrer_reward_value');
            $table->integer('coupon_validity_days')->default(30)->after('validation_window_days');
        });
    }

    public function down(): void
    {
        Schema::table('referral_rules', function (Blueprint $table) {
            $table->dropColumn(['tiered_rewards', 'coupon_validity_days']);
        });
    }
};

