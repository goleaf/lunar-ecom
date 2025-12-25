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
        Schema::create($this->prefix.'recommendation_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_product_id')->nullable()->constrained($this->prefix.'products')->onDelete('set null');
            $table->foreignId('recommended_product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id')->nullable()->index();
            
            // Recommendation context
            $table->string('recommendation_type')->index(); // related, frequently_bought_together, cross_sell, personalized, manual
            $table->string('recommendation_algorithm')->nullable()->index(); // For A/B testing
            $table->string('display_location')->index(); // product_page, cart, checkout, post_purchase
            
            // Tracking
            $table->boolean('converted')->default(false)->index(); // Did user purchase after click?
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->onDelete('set null');
            $table->timestamp('clicked_at')->index();
            $table->timestamps();
            
            // Indexes for analytics
            $table->index(['recommended_product_id', 'converted']);
            $table->index(['recommendation_type', 'clicked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'recommendation_clicks');
    }
};
