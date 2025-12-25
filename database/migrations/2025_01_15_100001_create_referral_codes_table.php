<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained('referral_programs')->onDelete('cascade');
            
            // Code identification
            $table->string('code')->unique();
            $table->string('slug')->unique()->nullable(); // For URL-friendly links
            
            // Ownership
            $table->foreignId('referrer_id')->nullable()->constrained('users')->onDelete('set null'); // User who owns this code
            $table->foreignId('referrer_customer_id')->nullable()->constrained('lunar_customers')->onDelete('set null'); // Customer who owns this code
            
            // Code settings
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_uses')->nullable(); // Null = unlimited
            $table->integer('current_uses')->default(0);
            
            // Custom link/URL
            $table->string('custom_url')->nullable(); // Custom tracking URL
            
            // Tracking
            $table->integer('total_clicks')->default(0);
            $table->integer('total_signups')->default(0);
            $table->integer('total_purchases')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            
            // Metadata
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['referral_program_id', 'is_active']);
            $table->index(['referrer_id', 'is_active']);
            $table->index(['referrer_customer_id', 'is_active']);
            $table->index('code');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_codes');
    }
};

