<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Pivot table for attribute groups and attributes:
     * - Attributes within groups
     * - Attribute ordering within groups
     * - Conditional visibility
     */
    public function up(): void
    {
        Schema::create($this->prefix.'attribute_group_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_group_id')
                ->constrained($this->prefix.'attribute_groups')
                ->onDelete('cascade');
            $table->foreignId('attribute_id')
                ->constrained($this->prefix.'attributes')
                ->onDelete('cascade');
            
            // Ordering
            $table->unsignedInteger('position')->default(0)->index();
            
            // Conditional visibility
            $table->json('visibility_conditions')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->boolean('is_required')->default(false)->index();
            
            // Display settings
            $table->string('label_override')->nullable();
            $table->text('help_text')->nullable();
            $table->json('display_config')->nullable();
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['attribute_group_id', 'attribute_id'], 'group_attribute_unique');
            
            // Indexes
            $table->index(['attribute_group_id', 'position']);
            $table->index(['attribute_group_id', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'attribute_group_attributes');
    }
};


