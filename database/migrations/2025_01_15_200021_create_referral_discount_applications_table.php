<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_discount_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('lunar_orders')->onDelete('cascade');
            $table->foreignId('cart_id')->nullable()->constrained('lunar_carts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referral_attribution_id')->constrained('referral_attributions')->onDelete('cascade');
            $table->foreignId('referral_program_id')->constrained('referral_programs')->onDelete('cascade');
            $table->json('applied_rule_ids');
            $table->json('applied_discounts'); // Array of discount details
            $table->decimal('total_discount_amount', 10, 2);
            $table->string('stage'); // cart, checkout, payment
            $table->json('audit_snapshot');
            $table->timestamps();

            $table->index(['order_id', 'user_id']);
            $table->index(['cart_id', 'user_id']);
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_discount_applications');
    }
};


