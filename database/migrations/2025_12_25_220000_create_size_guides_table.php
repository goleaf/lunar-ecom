<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'size_guides', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('measurement_unit', ['cm', 'inches'])->default('cm');
            
            // Associations
            $table->foreignId('category_id')->nullable()->constrained($this->prefix.'collections')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained($this->prefix.'brands')->nullOnDelete();
            
            // Regional support
            $table->string('region')->nullable()->index(); // e.g., 'US', 'EU', 'UK', 'ASIA'
            $table->json('supported_regions')->nullable(); // Array of supported regions
            
            // Size system
            $table->enum('size_system', ['us', 'eu', 'uk', 'asia', 'custom'])->default('us')->index();
            $table->json('size_labels')->nullable(); // Custom size labels if needed
            
            // Display settings
            $table->boolean('is_active')->default(true)->index();
            $table->integer('display_order')->default(0)->index();
            $table->string('image')->nullable(); // Size guide image
            
            // Conversion tables
            $table->json('conversion_table')->nullable(); // US/EU/UK size conversions
            
            $table->timestamps();
            
            // Indexes
            $table->index(['category_id', 'is_active']);
            $table->index(['brand_id', 'is_active']);
            $table->index(['region', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'size_guides');
    }
};


