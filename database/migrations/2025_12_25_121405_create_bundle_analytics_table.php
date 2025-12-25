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
        Schema::create($this->prefix.'bundle_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained($this->prefix.'bundles')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->onDelete('set null');
            
            // Event tracking
            $table->string('event_type')->index(); // view, add_to_cart, purchase, return
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id')->nullable()->index();
            
            // Bundle details at time of event
            $table->json('selected_items')->nullable(); // For dynamic bundles
            $table->decimal('bundle_price', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('savings_amount', 10, 2)->nullable();
            $table->decimal('savings_percentage', 5, 2)->nullable();
            
            $table->timestamp('event_at')->index();
            $table->timestamps();
            
            // Indexes for analytics
            $table->index(['bundle_id', 'event_type', 'event_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'bundle_analytics');
    }
};
