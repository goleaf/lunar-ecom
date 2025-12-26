<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for variant templates/presets.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Template type
            $table->enum('type', ['preset', 'template', 'pattern'])
                ->default('template')
                ->index();
            
            // Product type scope (null = all product types)
            $table->foreignId('product_type_id')
                ->nullable()
                ->constrained($this->prefix.'product_types')
                ->nullOnDelete();
            
            // Default attribute combination
            $table->json('default_combination')->nullable();
            
            // Default variant fields
            $table->json('default_fields')->nullable();
            // Example: {"stock": 0, "enabled": true, "purchasable": "in_stock"}
            
            // Attribute configuration
            $table->json('attribute_config')->nullable();
            // Example: {"defining": [1, 2], "informational": [3, 4]}
            
            // Usage count
            $table->integer('usage_count')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_type_id', 'is_active']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_templates');
    }
};


