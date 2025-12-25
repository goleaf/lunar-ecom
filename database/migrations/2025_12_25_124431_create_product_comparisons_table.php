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
        Schema::create($this->prefix.'product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            
            // Product IDs stored as JSON array (max 5 products)
            $table->json('product_ids');
            
            // Selected attributes for comparison (optional)
            $table->json('selected_attributes')->nullable();
            
            // Expiration
            $table->timestamp('expires_at')->index();
            
            $table->timestamps();
            
            // Unique constraint: one comparison per user/session
            $table->unique(['user_id']);
            $table->unique(['session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_comparisons');
    }
};
