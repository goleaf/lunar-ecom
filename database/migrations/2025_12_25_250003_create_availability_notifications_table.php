<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'availability_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained($this->prefix.'availability_bookings')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->nullOnDelete();
            
            // Notification details
            $table->enum('notification_type', [
                'availability_changed',
                'booking_confirmed',
                'booking_cancelled',
                'booking_reminder',
                'blackout_date_added',
            ])->index();
            
            $table->text('message');
            $table->json('metadata')->nullable(); // Additional data
            
            // Status
            $table->boolean('is_sent')->default(false)->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('email')->nullable(); // Email address if customer not logged in
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_sent']);
            $table->index(['booking_id', 'is_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'availability_notifications');
    }
};


