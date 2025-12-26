<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for attribute dependency rules.
     * Example: "XL only exists in Black"
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_attribute_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained($this->prefix.'products')
                ->nullOnDelete();
            
            // Dependency type
            $table->enum('type', [
                'requires',      // If A is selected, B is required
                'excludes',      // If A is selected, B cannot be selected
                'allows_only',   // If A is selected, only B, C, D are allowed
                'requires_one_of', // If A is selected, at least one of B, C, D is required
            ])->index();
            
            // Source attribute (the one that triggers the rule)
            $table->foreignId('source_option_id')
                ->constrained($this->prefix.'product_options')
                ->onDelete('cascade');
            $table->foreignId('source_value_id')
                ->nullable()
                ->constrained($this->prefix.'product_option_values')
                ->nullOnDelete();
            
            // Target attributes (affected by the rule)
            $table->foreignId('target_option_id')
                ->constrained($this->prefix.'product_options')
                ->onDelete('cascade');
            $table->json('target_value_ids')->nullable(); // Array of allowed/excluded value IDs
            
            // Rule configuration
            $table->json('config')->nullable();
            // Example: {"message": "XL only available in Black", "enforce": true}
            
            // Priority (for multiple rules)
            $table->integer('priority')->default(0)->index();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index(['source_option_id', 'source_value_id']);
            $table->index(['target_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_attribute_dependencies');
    }
};


