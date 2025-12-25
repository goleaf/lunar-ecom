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
        Schema::create($this->prefix.'product_purchase_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('associated_product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            
            // Co-purchase metrics
            $table->unsignedInteger('co_purchase_count')->default(0)->index();
            $table->decimal('confidence', 5, 4)->default(0)->index(); // Association rule confidence (0-1)
            $table->decimal('support', 5, 4)->default(0)->index(); // Association rule support (0-1)
            $table->decimal('lift', 5, 4)->default(0)->index(); // Association rule lift
            
            // Direction: product_id -> associated_product_id
            $table->unique(['product_id', 'associated_product_id']);
            $table->index(['product_id', 'co_purchase_count']);
            $table->index(['associated_product_id', 'co_purchase_count']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_purchase_associations');
    }
};
