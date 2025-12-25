<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained('referral_codes')->onDelete('cascade');
            
            // Visitor tracking
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('landing_page')->nullable();
            
            // User identification (if available)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('lunar_customers')->onDelete('set null');
            
            // Event tracking
            $table->string('event_type'); // click, signup, purchase, etc.
            $table->json('event_data')->nullable();
            
            // Conversion tracking
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('conversion_order_id')->nullable()->constrained('lunar_orders')->onDelete('set null');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['referral_code_id', 'event_type', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('converted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_tracking');
    }
};

