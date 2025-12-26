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
        Schema::create($this->prefix.'product_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            
            // Customization definition
            $table->enum('customization_type', ['text', 'image', 'option', 'color', 'number', 'date'])->index();
            $table->string('field_name')->index(); // Internal field identifier (e.g., 'engraving_text', 'logo_upload')
            $table->string('field_label'); // Display label (e.g., 'Engraving Text', 'Upload Logo')
            $table->text('description')->nullable(); // Help text for customers
            $table->text('placeholder')->nullable(); // Placeholder text
            
            // Validation rules
            $table->boolean('is_required')->default(false)->index();
            $table->integer('min_length')->nullable(); // For text fields
            $table->integer('max_length')->nullable(); // For text fields
            $table->string('pattern')->nullable(); // Regex pattern for validation
            $table->json('allowed_values')->nullable(); // For option/select fields
            
            // Image-specific settings
            $table->json('allowed_formats')->nullable(); // ['jpg', 'png', 'svg'] for image type
            $table->integer('max_file_size_kb')->nullable(); // Max file size in KB
            $table->integer('min_width')->nullable(); // Min image width in pixels
            $table->integer('max_width')->nullable(); // Max image width in pixels
            $table->integer('min_height')->nullable(); // Min image height in pixels
            $table->integer('max_height')->nullable(); // Max image height in pixels
            $table->integer('aspect_ratio_width')->nullable(); // For aspect ratio validation
            $table->integer('aspect_ratio_height')->nullable();
            
            // Pricing
            $table->decimal('price_modifier', 10, 2)->default(0); // Additional cost
            $table->enum('price_modifier_type', ['fixed', 'per_character', 'per_image'])->default('fixed');
            
            // Display settings
            $table->integer('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('show_in_preview')->default(true); // Show in product preview
            
            // Preview settings
            $table->json('preview_settings')->nullable(); // {
            //   "position": {"x": 100, "y": 200},
            //   "font": "Arial",
            //   "font_size": 24,
            //   "color": "#000000",
            //   "rotation": 0,
            //   "opacity": 1.0
            // }
            
            // Template/example
            $table->string('template_image')->nullable(); // Example image
            $table->json('example_values')->nullable(); // Example values for preview
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_active', 'display_order']);
            $table->unique(['product_id', 'field_name'], 'product_field_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_customizations');
    }
};


