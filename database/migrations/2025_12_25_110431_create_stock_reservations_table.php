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
        Schema::create($this->prefix.'stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained($this->prefix.'warehouses')->onDelete('cascade');
            $table->foreignId('inventory_level_id')->constrained($this->prefix.'inventory_levels')->onDelete('cascade');
            
            // Reservation details
            $table->integer('quantity');
            $table->string('reference_type')->nullable(); // Order, Cart, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Expiration
            $table->timestamp('expires_at')->index();
            $table->boolean('is_released')->default(false)->index();
            $table->timestamp('released_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['reference_type', 'reference_id']);
            $table->index(['expires_at', 'is_released']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'stock_reservations');
    }
};
