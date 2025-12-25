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
        Schema::create($this->prefix.'stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->cascadeOnDelete();
            $table->string('customer_email')->index();
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->cascadeOnDelete();
            
            // Notification tracking
            $table->timestamp('notification_sent_at')->nullable()->index();
            $table->string('token')->unique()->index(); // For unsubscribe
            
            // Preferences
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('subscribed_at')->index();
            $table->timestamp('expires_at')->nullable()->index(); // Auto-remove after 90 days
            
            $table->timestamps();
            
            // Prevent duplicate subscriptions (one per email per variant)
            $table->unique(['product_variant_id', 'customer_email'], 'unique_variant_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'stock_notifications');
    }
};

