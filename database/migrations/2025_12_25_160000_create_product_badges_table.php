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
        Schema::create($this->prefix.'product_badges', function (Blueprint $table) {
            $table->id();
            
            // Badge information
            $table->string('name')->unique();
            $table->string('handle')->unique()->index();
            $table->string('type')->default('custom')->index(); // new, sale, hot, limited, exclusive, custom
            $table->text('description')->nullable();
            
            // Display settings
            $table->string('label')->nullable(); // Display text (overrides name)
            $table->string('color')->default('#000000'); // Text color
            $table->string('background_color')->default('#FFFFFF'); // Background color
            $table->string('border_color')->nullable(); // Border color
            $table->string('icon')->nullable(); // Icon class or SVG
            $table->string('position')->default('top-left')->index(); // top-left, top-right, bottom-left, bottom-right, center
            
            // Styling
            $table->string('style')->default('rounded'); // rounded, square, pill, custom
            $table->integer('font_size')->default(12);
            $table->integer('padding_x')->default(8);
            $table->integer('padding_y')->default(4);
            $table->integer('border_radius')->default(4);
            $table->boolean('show_icon')->default(false);
            $table->boolean('animated')->default(false);
            $table->string('animation_type')->nullable(); // pulse, bounce, flash, etc.
            
            // Display rules
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->index(); // Higher priority badges shown first
            $table->integer('max_display_count')->nullable(); // Max number of badges to show per product
            
            // Auto-assignment rules
            $table->boolean('auto_assign')->default(false)->index();
            $table->json('assignment_rules')->nullable(); // Conditions for automatic assignment
            
            // Visibility
            $table->json('display_conditions')->nullable(); // When to show the badge
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'priority']);
            $table->index(['auto_assign', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_badges');
    }
};

