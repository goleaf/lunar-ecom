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
        Schema::create($this->prefix.'product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained($this->prefix.'attributes')->onDelete('cascade');
            
            // Value stored as JSON for flexibility (supports different types)
            $table->json('value');
            
            // For numeric attributes, store raw numeric value for efficient filtering
            $table->decimal('numeric_value', 15, 4)->nullable()->index();
            
            // For text attributes, store searchable text
            $table->string('text_value')->nullable()->index();
            
            $table->timestamps();
            
            // Ensure a product can only have one value per attribute
            $table->unique(['product_id', 'attribute_id']);
            
            // Indexes for filtering
            $table->index(['attribute_id', 'numeric_value']);
            $table->index(['attribute_id', 'text_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_attribute_values');
    }
};
