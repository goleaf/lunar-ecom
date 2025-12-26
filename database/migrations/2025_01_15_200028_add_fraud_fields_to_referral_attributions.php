<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_attributions', function (Blueprint $table) {
            $table->string('device_fingerprint_hash', 64)->nullable()->after('attribution_method');
            $table->string('ip_hash', 64)->nullable()->after('device_fingerprint_hash');
            $table->string('payment_fingerprint_hash', 64)->nullable()->after('ip_hash');
            $table->integer('risk_score')->default(0)->after('payment_fingerprint_hash');
            $table->json('risk_factors')->nullable()->after('risk_score');
            $table->boolean('rewards_held')->default(false)->after('risk_factors');
            $table->timestamp('rewards_held_until')->nullable()->after('rewards_held');
            $table->timestamp('rewards_released_at')->nullable()->after('rewards_held_until');
        });
    }

    public function down(): void
    {
        Schema::table('referral_attributions', function (Blueprint $table) {
            $table->dropColumn([
                'device_fingerprint_hash',
                'ip_hash',
                'payment_fingerprint_hash',
                'risk_score',
                'risk_factors',
                'rewards_held',
                'rewards_held_until',
                'rewards_released_at',
            ]);
        });
    }
};


