<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for channel-specific attribute values.
     * Allows different attribute values per channel (e.g., different descriptions per market).
     */
    public function up(): void
    {
        Schema::create($this->prefix.'channel_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('channel_id')->constrained($this->prefix.'channels')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained($this->prefix.'attributes')->onDelete('cascade');
            
            // Value stored as JSON for flexibility (supports different types)
            $table->json('value');
            
            // For numeric attributes, store raw numeric value for efficient filtering
            $table->decimal('numeric_value', 15, 4)->nullable()->index();
            
            // For text attributes, store searchable text
            $table->string('text_value')->nullable()->index();
            
            $table->timestamps();
            
            // Ensure a product can only have one value per attribute per channel
            $table->unique(['product_id', 'channel_id', 'attribute_id']);
            
            // Indexes for filtering
            $table->index(['channel_id', 'attribute_id', 'numeric_value']);
            $table->index(['channel_id', 'attribute_id', 'text_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'channel_attribute_values');
    }
};

