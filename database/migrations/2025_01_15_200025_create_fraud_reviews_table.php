<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_attribution_id')->constrained('referral_attributions')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected', 'escalated'])->default('pending');
            $table->integer('risk_score')->default(0); // 0-100
            $table->json('risk_factors'); // Array of risk factors
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'risk_score']);
            $table->index('referral_attribution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_reviews');
    }
};


