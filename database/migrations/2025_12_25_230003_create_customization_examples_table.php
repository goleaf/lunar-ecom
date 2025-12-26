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
        Schema::create($this->prefix.'customization_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained($this->prefix.'products')->nullOnDelete();
            $table->foreignId('customization_id')->nullable()->constrained($this->prefix.'product_customizations')->nullOnDelete();
            
            // Example data
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('example_image'); // Before/after or example result
            $table->json('customization_values')->nullable(); // The values used in this example
            
            // Display
            $table->integer('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index(['customization_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'customization_examples');
    }
};


