<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_rules', function (Blueprint $table) {
            $table->integer('hold_rewards_days')->nullable()->after('validation_window_days');
            $table->boolean('require_manual_review')->default(false)->after('hold_rewards_days');
        });
    }

    public function down(): void
    {
        Schema::table('referral_rules', function (Blueprint $table) {
            $table->dropColumn(['hold_rewards_days', 'require_manual_review']);
        });
    }
};


