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
        Schema::create($this->prefix.'customization_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable()->index(); // e.g., 'engraving', 'monogram', 'logo'
            
            // Template data
            $table->json('template_data')->nullable(); // {
            //   "font": "Arial",
            //   "font_size": 24,
            //   "color": "#000000",
            //   "position": {"x": 100, "y": 200},
            //   "text": "Sample Text"
            // }
            
            // Preview image
            $table->string('preview_image')->nullable();
            
            // Usage
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'customization_templates');
    }
};


