<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_programs', function (Blueprint $table) {
            $table->boolean('last_click_wins')->default(true)->after('terms_url');
            $table->integer('attribution_ttl_days')->default(7)->after('last_click_wins');
        });
    }

    public function down(): void
    {
        Schema::table('referral_programs', function (Blueprint $table) {
            $table->dropColumn(['last_click_wins', 'attribution_ttl_days']);
        });
    }
};


