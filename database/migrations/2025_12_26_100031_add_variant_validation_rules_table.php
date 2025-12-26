<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for variant validation rules:
     * - Shipping eligibility rules
     * - Channel availability rules
     * - Country restrictions
     * - Customer-group restrictions
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_validation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->string('rule_type')->index(); // shipping_eligibility, channel_availability, country_restriction, customer_group_restriction
            $table->string('rule_name'); // Name/identifier for the rule
            $table->text('rule_description')->nullable();
            $table->json('conditions')->nullable(); // Rule conditions (e.g., weight limits, dimensions)
            $table->json('restrictions')->nullable(); // Restrictions (e.g., blocked countries, channels)
            $table->json('allowed_values')->nullable(); // Allowed values (e.g., allowed countries, channels)
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->index(); // Higher priority rules are checked first
            $table->timestamps();

            $table->index(['product_variant_id', 'rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_validation_rules');
    }
};


