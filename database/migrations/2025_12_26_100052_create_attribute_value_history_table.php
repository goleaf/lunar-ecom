<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for attribute value change history:
     * - Track all changes to attribute values
     * - Store before/after values
     * - Track who made the change
     */
    public function up(): void
    {
        Schema::create($this->prefix.'attribute_value_history', function (Blueprint $table) {
            $table->id();
            
            // Reference to the value (polymorphic)
            $table->morphs('valueable'); // product_attribute_value, variant_attribute_value, channel_attribute_value
            $table->foreignId('attribute_id')
                ->constrained($this->prefix.'attributes')
                ->onDelete('cascade');
            
            // Value changes
            $table->json('value_before')->nullable();
            $table->json('value_after')->nullable();
            $table->decimal('numeric_value_before', 15, 4)->nullable();
            $table->decimal('numeric_value_after', 15, 4)->nullable();
            $table->string('text_value_before')->nullable();
            $table->string('text_value_after')->nullable();
            
            // Change metadata
            $table->string('change_type')->index(); // created, updated, deleted
            $table->string('locale', 10)->nullable()->index();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('change_reason')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata
            
            $table->timestamps();
            
            // Indexes
            $table->index(['valueable_type', 'valueable_id', 'attribute_id']);
            $table->index(['changed_by', 'created_at']);
            $table->index(['change_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'attribute_value_history');
    }
};


