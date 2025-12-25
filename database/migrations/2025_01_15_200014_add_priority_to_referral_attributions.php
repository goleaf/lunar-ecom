<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_attributions', function (Blueprint $table) {
            $table->integer('priority')->default(2)->after('attribution_method');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::table('referral_attributions', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropColumn('priority');
        });
    }
};

