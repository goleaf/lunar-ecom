<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for attribute value normalization.
     * Normalizes different spellings/values to a canonical form.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_attribute_normalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')
                ->constrained($this->prefix.'product_options')
                ->onDelete('cascade');
            
            // Source value (what user enters)
            $table->string('source_value', 255)->index();
            
            // Normalized value (canonical form)
            $table->foreignId('normalized_value_id')
                ->constrained($this->prefix.'product_option_values')
                ->onDelete('cascade');
            
            // Normalization type
            $table->enum('type', [
                'synonym',      // Alternative name
                'alias',        // Alias
                'normalize',    // Normalize spelling/casing
                'map',          // Map to existing value
            ])->default('normalize');
            
            // Case sensitivity
            $table->boolean('case_sensitive')->default(false);
            
            // Priority (for multiple normalizations)
            $table->integer('priority')->default(0)->index();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['option_id', 'source_value']);
            $table->index(['normalized_value_id']);
            $table->unique(['option_id', 'source_value', 'normalized_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_attribute_normalizations');
    }
};


