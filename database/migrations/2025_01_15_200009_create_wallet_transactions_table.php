<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit', 'hold', 'release'])->default('credit');
            $table->decimal('amount', 15, 2);
            $table->string('reason'); // referral_reward, refund_adjustment, fraud_reversal, etc.
            $table->foreignId('related_order_id')->nullable()->constrained('lunar_orders')->onDelete('set null');
            $table->foreignId('related_referral_id')->nullable()->constrained('referral_attributions')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['wallet_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('related_order_id');
            $table->index('related_referral_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};


