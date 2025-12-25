<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Optional table for storing pricing snapshots for audit trail.
     * Can be used for debugging or compliance purposes.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'cart_pricing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained($this->prefix.'carts')->onDelete('cascade');
            
            // Snapshot metadata
            $table->enum('snapshot_type', ['calculation', 'checkout'])->default('calculation');
            $table->json('pricing_data'); // Complete pricing state snapshot
            
            // Context
            $table->string('trigger')->nullable(); // What triggered this snapshot
            $table->string('pricing_version')->nullable(); // Version at time of snapshot
            
            $table->timestamps();
            
            // Indexes
            $table->index(['cart_id', 'snapshot_type']);
            $table->index('created_at');
            $table->index('trigger');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'cart_pricing_snapshots');
    }
};

