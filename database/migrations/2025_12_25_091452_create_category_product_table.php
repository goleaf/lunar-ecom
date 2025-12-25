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
        Schema::create($this->prefix.'category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained($this->prefix.'categories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->integer('position')->default(0)->index();
            $table->timestamps();
            
            // Ensure a product can only be in a category once
            $table->unique(['category_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'category_product');
    }
};
