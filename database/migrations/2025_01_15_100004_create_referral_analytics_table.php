<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained('referral_programs')->onDelete('cascade');
            $table->foreignId('referral_code_id')->nullable()->constrained('referral_codes')->onDelete('set null');
            
            // Date aggregation
            $table->date('date');
            
            // Metrics
            $table->integer('clicks')->default(0);
            $table->integer('signups')->default(0);
            $table->integer('first_purchases')->default(0);
            $table->integer('repeat_purchases')->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('rewards_issued')->default(0);
            $table->decimal('rewards_value', 15, 2)->default(0);
            
            // Conversion rates
            $table->decimal('click_to_signup_rate', 5, 2)->default(0);
            $table->decimal('signup_to_purchase_rate', 5, 2)->default(0);
            $table->decimal('overall_conversion_rate', 5, 2)->default(0);
            
            // Aggregation level
            $table->string('aggregation_level')->default('daily'); // daily, weekly, monthly
            
            $table->timestamps();
            
            $table->unique(['referral_program_id', 'referral_code_id', 'date', 'aggregation_level'], 'unique_analytics');
            $table->index(['referral_program_id', 'date']);
            $table->index(['referral_code_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_analytics');
    }
};


