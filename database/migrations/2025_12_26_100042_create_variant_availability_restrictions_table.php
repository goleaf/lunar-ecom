<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for variant availability restrictions:
     * - Country-based restrictions
     * - Channel-based restrictions
     * - Customer-group restrictions
     */
    public function up(): void
    {
        Schema::create($this->prefix.'variant_availability_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->enum('restriction_type', [
                'country',          // Country-based restriction
                'channel',          // Channel-based restriction
                'customer_group',   // Customer-group restriction
            ])->index();
            $table->string('restriction_value')->index(); // Country code, channel ID, customer group ID
            $table->enum('action', ['allow', 'deny'])->default('deny')->index();
            $table->text('reason')->nullable(); // Reason for restriction
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(0)->index(); // Higher priority = checked first
            $table->timestamps();

            // Unique constraint: one restriction per variant per type per value
            $table->unique(['product_variant_id', 'restriction_type', 'restriction_value'], 'variant_restriction_unique');
            
            // Indexes for performance
            $table->index(['restriction_type', 'restriction_value', 'is_active']);
            $table->index(['product_variant_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_availability_restrictions');
    }
};


