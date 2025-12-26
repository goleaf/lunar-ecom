<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_matrix_id')->constrained($this->prefix.'price_matrices')->cascadeOnDelete();
            
            // Rule definition
            $table->enum('rule_type', ['quantity', 'customer_group', 'region', 'date', 'product', 'variant', 'custom'])->index();
            $table->string('rule_key')->nullable(); // e.g., 'quantity', 'customer_group_id', 'country_code'
            $table->enum('operator', ['=', '!=', '>', '>=', '<', '<=', 'in', 'not_in', 'between'])->default('=');
            $table->text('rule_value')->nullable(); // Can be single value, JSON array, or JSON object for 'between'
            
            // Price adjustment
            $table->decimal('price', 15, 2)->nullable(); // Fixed price
            $table->decimal('price_adjustment', 10, 2)->nullable(); // Amount to add/subtract
            $table->integer('percentage_discount')->nullable(); // Percentage discount
            $table->enum('adjustment_type', ['fixed', 'add', 'subtract', 'percentage', 'override'])->default('fixed');
            
            // Priority within matrix
            $table->integer('priority')->default(0)->index();
            
            // Conditions (for complex rules)
            $table->json('conditions')->nullable(); // Additional conditions
            
            $table->timestamps();
            
            // Indexes
            $table->index(['price_matrix_id', 'rule_type', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'pricing_rules');
    }
};


