<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained('referral_programs')->onDelete('cascade');
            $table->foreignId('referral_code_id')->constrained('referral_codes')->onDelete('cascade');
            
            // Event details
            $table->string('event_type'); // signup, first_purchase, repeat_purchase, etc.
            $table->string('status')->default('pending'); // pending, processed, failed, cancelled
            
            // Participants
            $table->foreignId('referrer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('referrer_customer_id')->nullable()->constrained('lunar_customers')->onDelete('set null');
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('referee_customer_id')->nullable()->constrained('lunar_customers')->onDelete('set null');
            
            // Related entities
            $table->foreignId('order_id')->nullable()->constrained('lunar_orders')->onDelete('set null');
            $table->string('order_reference')->nullable();
            
            // Reward details
            $table->json('reward_config')->nullable(); // The reward configuration that triggered
            $table->decimal('reward_value', 15, 2)->nullable();
            $table->foreignId('reward_currency_id')->nullable()->constrained('lunar_currencies')->onDelete('set null');
            $table->string('reward_type')->nullable(); // discount, credit, percentage, etc.
            
            // Processing
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_notes')->nullable();
            $table->text('error_message')->nullable();
            
            // Tracking
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['referral_program_id', 'event_type', 'status']);
            $table->index(['referral_code_id', 'status']);
            $table->index(['referrer_id', 'status']);
            $table->index(['referee_id', 'status']);
            $table->index('order_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_events');
    }
};


