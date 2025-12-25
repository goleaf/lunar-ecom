<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'categories', function (Blueprint $table) {
            $table->id();
            
            // Nested set fields (provided by kalnoy/nestedset)
            $table->nestedSet();
            
            // Translatable name (stored as JSON)
            $table->json('name');
            
            // Slug (auto-generated, unique)
            $table->string('slug')->unique()->index();
            
            // Description (rich text, translatable)
            $table->json('description')->nullable();
            
            // SEO fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            // Display and status fields
            $table->integer('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('show_in_navigation')->default(true)->index();
            
            // Icon (stored as string, e.g., icon class name or SVG path)
            $table->string('icon')->nullable();
            
            // Cached product count
            $table->unsignedInteger('product_count')->default(0)->index();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'categories');
    }
};
