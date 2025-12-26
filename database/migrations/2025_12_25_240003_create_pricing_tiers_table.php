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
        Schema::create($this->prefix.'pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_matrix_id')->constrained($this->prefix.'price_matrices')->cascadeOnDelete();
            
            // Tier definition
            $table->string('tier_name')->nullable(); // e.g., "Bulk 1", "Wholesale"
            $table->integer('min_quantity')->default(1)->index();
            $table->integer('max_quantity')->nullable()->index();
            
            // Pricing
            $table->decimal('price', 15, 2)->nullable(); // Fixed price for this tier
            $table->decimal('price_adjustment', 10, 2)->nullable(); // Amount adjustment
            $table->integer('percentage_discount')->nullable(); // Percentage discount
            $table->enum('pricing_type', ['fixed', 'adjustment', 'percentage'])->default('fixed');
            
            // Display
            $table->integer('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['price_matrix_id', 'min_quantity', 'max_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'pricing_tiers');
    }
};


