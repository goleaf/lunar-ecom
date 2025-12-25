<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gdpr_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'export', 'deletion', 'anonymization', 'rectification'
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'rejected', 'failed'
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('lunar_customers')->onDelete('cascade');
            $table->string('email');
            $table->string('verification_token')->unique()->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('export_file_path')->nullable();
            $table->json('request_data')->nullable();
            $table->json('processing_log')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('email');
            $table->index('verification_token');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gdpr_requests');
    }
};
