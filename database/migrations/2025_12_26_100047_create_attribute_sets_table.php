<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for attribute sets:
     * - Attribute sets per product type
     * - Inheritance between sets
     * - Conditional visibility
     */
    public function up(): void
    {
        Schema::create($this->prefix.'attribute_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            
            // Product type association
            $table->foreignId('product_type_id')
                ->nullable()
                ->constrained($this->prefix.'product_types')
                ->onDelete('cascade');
            
            // Inheritance
            $table->foreignId('parent_set_id')
                ->nullable()
                ->constrained($this->prefix.'attribute_sets')
                ->onDelete('set null');
            
            // Settings
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('position')->default(0)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_type_id', 'is_active']);
            $table->index(['parent_set_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'attribute_sets');
    }
};


