<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Pivot table for attribute sets and groups:
     * - Groups within sets
     * - Group ordering within sets
     * - Conditional visibility
     */
    public function up(): void
    {
        Schema::create($this->prefix.'attribute_set_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_set_id')
                ->constrained($this->prefix.'attribute_sets')
                ->onDelete('cascade');
            $table->foreignId('attribute_group_id')
                ->constrained($this->prefix.'attribute_groups')
                ->onDelete('cascade');
            
            // Ordering
            $table->unsignedInteger('position')->default(0)->index();
            
            // Conditional visibility
            $table->json('visibility_conditions')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            
            // Settings
            $table->boolean('is_collapsible')->default(false);
            $table->boolean('is_collapsed_by_default')->default(false);
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['attribute_set_id', 'attribute_group_id'], 'set_group_unique');
            
            // Indexes
            $table->index(['attribute_set_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'attribute_set_groups');
    }
};


