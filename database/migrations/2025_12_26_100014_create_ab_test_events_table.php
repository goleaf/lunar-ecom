<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for tracking A/B test events.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'ab_test_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')
                ->constrained($this->prefix.'product_ab_tests')
                ->onDelete('cascade');
            
            // User/Session
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            
            // Variant shown
            $table->enum('variant', ['a', 'b'])->index();
            
            // Event type
            $table->enum('event_type', [
                'view',
                'add_to_cart',
                'remove_from_cart',
                'checkout_started',
                'purchase',
                'bounce',
            ])->index();
            
            // Event data
            $table->json('event_data')->nullable();
            
            $table->timestamp('occurred_at')->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['ab_test_id', 'variant', 'event_type']);
            $table->index(['ab_test_id', 'occurred_at']);
            $table->index(['session_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'ab_test_events');
    }
};

