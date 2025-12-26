<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'contract_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained($this->prefix.'b2b_contracts')->onDelete('cascade');
            $table->string('rule_type')->index(); // 'price_override', 'promotion_override', 'payment_method', 'shipping', 'discount'
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->index();
            $table->json('conditions')->nullable(); // Rule conditions (e.g., cart total, product categories)
            $table->json('actions')->nullable(); // Rule actions (e.g., allowed payment methods, shipping rules)
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['contract_id', 'rule_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'contract_rules');
    }
};


