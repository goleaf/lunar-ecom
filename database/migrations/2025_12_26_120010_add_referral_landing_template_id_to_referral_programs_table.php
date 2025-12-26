<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_programs', function (Blueprint $table) {
            $table->foreignId('referral_landing_template_id')
                ->nullable()
                ->after('terms_url')
                ->constrained('referral_landing_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referral_programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_landing_template_id');
        });
    }
};


