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
        Schema::create($this->prefix.'product_badge_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_badge_id')->constrained($this->prefix.'product_badges')->onDelete('cascade');
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            
            // Assignment details
            $table->boolean('is_auto_assigned')->default(false)->index();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            
            // Display settings (override badge defaults)
            $table->string('position')->nullable();
            $table->integer('priority')->nullable();
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['product_badge_id', 'product_id'], 'unique_product_badge');
            
            // Indexes
            $table->index(['product_id', 'is_auto_assigned']);
            $table->index(['expires_at', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_badge_product');
    }
};

