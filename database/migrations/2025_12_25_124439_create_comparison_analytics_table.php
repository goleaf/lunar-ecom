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
        Schema::create($this->prefix.'comparison_analytics', function (Blueprint $table) {
            $table->id();
            
            // Track which products are compared together
            $table->json('product_ids'); // Array of product IDs that were compared
            $table->unsignedInteger('comparison_count')->default(1)->index(); // How many times these products were compared together
            
            // User/session tracking
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id')->nullable()->index();
            
            $table->timestamp('compared_at')->index();
            $table->timestamps();
            
            // Index for finding common comparisons
            $table->index(['product_ids', 'compared_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'comparison_analytics');
    }
};
