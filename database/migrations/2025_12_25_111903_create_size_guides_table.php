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
        Schema::create('size_guides', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category_type')->nullable(); // 'clothing', 'shoes', 'accessories', etc.
            $table->enum('gender', ['men', 'women', 'unisex', 'kids'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->enum('measurement_unit', ['cm', 'inches', 'both'])->default('cm');
            $table->json('size_chart_data')->nullable(); // Size measurements in JSON format
            $table->timestamps();
            
            $table->index(['is_active', 'display_order']);
            $table->index('category_type');
            $table->index('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('size_guides');
    }
};
