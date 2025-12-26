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
        Schema::create($this->prefix.'product_size_guide', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('size_guide_id')->constrained($this->prefix.'size_guides')->cascadeOnDelete();
            $table->string('region')->nullable(); // Override region for this product
            $table->integer('priority')->default(0); // Higher priority guides shown first
            $table->timestamps();
            
            $table->unique(['product_id', 'size_guide_id', 'region'], 'product_size_guide_region_unique');
            $table->index(['product_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_size_guide');
    }
};


