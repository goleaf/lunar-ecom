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
        Schema::create($this->prefix.'order_item_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained($this->prefix.'order_lines')->cascadeOnDelete();
            $table->foreignId('customization_id')->constrained($this->prefix.'product_customizations')->cascadeOnDelete();
            
            // Customization value
            $table->text('value')->nullable(); // Text value or image path
            $table->string('value_type')->default('text'); // text, image, option, color
            
            // Image-specific data
            $table->string('image_path')->nullable(); // Stored image path
            $table->string('image_original_name')->nullable();
            $table->integer('image_width')->nullable();
            $table->integer('image_height')->nullable();
            $table->integer('image_size_kb')->nullable();
            
            // Pricing
            $table->decimal('additional_cost', 10, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            
            // Production notes (for fulfillment)
            $table->text('production_notes')->nullable();
            
            // Preview data (for reference)
            $table->json('preview_data')->nullable(); // Store preview configuration
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_item_id', 'customization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'order_item_customizations');
    }
};


