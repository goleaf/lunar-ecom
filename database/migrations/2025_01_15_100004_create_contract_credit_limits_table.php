<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'contract_credit_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained($this->prefix.'b2b_contracts')->onDelete('cascade');
            $table->integer('credit_limit')->default(0); // Credit limit in minor currency units
            $table->integer('current_balance')->default(0); // Current outstanding balance
            $table->integer('available_credit')->virtualAs('credit_limit - current_balance'); // Computed column
            $table->enum('payment_terms', ['net_7', 'net_15', 'net_30', 'net_60', 'net_90', 'immediate'])->default('net_30')->index();
            $table->integer('payment_terms_days')->default(30); // Number of days for payment terms
            $table->date('last_payment_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['contract_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'contract_credit_limits');
    }
};


