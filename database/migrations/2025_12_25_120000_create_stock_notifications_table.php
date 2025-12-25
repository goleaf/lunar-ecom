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
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->onDelete('cascade');
            
            // Contact information
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            
            // Notification status
            $table->enum('status', ['pending', 'sent', 'cancelled'])->default('pending')->index();
            $table->timestamp('notified_at')->nullable();
            $table->integer('notification_count')->default(0);
            
            // Preferences
            $table->boolean('notify_on_backorder')->default(false);
            $table->integer('min_quantity')->nullable(); // Only notify if stock >= this amount
            
            // Tracking
            $table->string('token')->unique()->index(); // Unique token for unsubscribe
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'status']);
            $table->index(['product_variant_id', 'status']);
            $table->index(['email', 'status']);
            $table->index('created_at');
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

