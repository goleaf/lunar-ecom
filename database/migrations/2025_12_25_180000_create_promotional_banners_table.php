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
        Schema::create($this->prefix.'promotional_banners', function (Blueprint $table) {
            $table->id();
            
            // Banner information
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            
            // Display settings
            $table->string('position')->default('top')->index(); // top, middle, bottom
            $table->integer('order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            
            // Links
            $table->string('link')->nullable();
            $table->string('link_text')->default('Shop Now');
            $table->string('link_type')->default('collection'); // collection, product, category, url
            
            // Targeting
            $table->json('display_conditions')->nullable(); // Conditions for displaying banner
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'position', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'promotional_banners');
    }
};

