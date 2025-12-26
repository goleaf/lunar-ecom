<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for tracking variant attribute combinations and rules.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_attribute_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->foreignId('variant_id')
                ->nullable()
                ->constrained($this->prefix.'product_variants')
                ->nullOnDelete();
            
            // Attribute combination (JSON)
            // Format: {"option_id": "value_id", "option_id": "value_id"}
            $table->json('combination')->index();
            
            // Combination hash for quick lookup
            $table->string('combination_hash', 64)->unique()->index();
            
            // Variant-defining attributes (required for variant)
            $table->json('defining_attributes')->nullable();
            
            // Informational attributes (optional, for display/filtering)
            $table->json('informational_attributes')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'active', 'disabled'])
                ->default('draft')
                ->index();
            
            // Is partial variant (missing some attributes)
            $table->boolean('is_partial')->default(false)->index();
            
            // Template/preset ID
            $table->foreignId('template_id')
                ->nullable()
                ->constrained($this->prefix.'variant_templates')
                ->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'is_partial']);
            $table->index(['variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_attribute_combinations');
    }
};


