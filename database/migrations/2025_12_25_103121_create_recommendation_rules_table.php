<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_product_id')->nullable()->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('recommended_product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            
            // Rule configuration
            $table->string('rule_type')->default('manual')->index(); // manual, category, attribute, etc.
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Additional conditions (category, price range, etc.)
            
            // Priority and display
            $table->unsignedInteger('priority')->default(0)->index(); // Higher priority = shown first
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('display_count')->default(0); // Track how many times shown
            $table->unsignedInteger('click_count')->default(0); // Track clicks
            $table->decimal('conversion_rate', 5, 4)->default(0); // clicks / displays
            
            // A/B testing
            $table->string('ab_test_variant')->nullable()->index(); // For A/B testing different algorithms
            
            $table->timestamps();
            
            // Prevent duplicate rules
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->unique(['source_product_id', 'recommended_product_id', 'rule_type']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'recommendation_rules');
    }
};
