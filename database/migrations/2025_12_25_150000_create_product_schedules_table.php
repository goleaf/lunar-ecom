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
        Schema::create($this->prefix.'product_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            
            // Schedule type
            $table->enum('type', ['publish', 'unpublish', 'flash_sale', 'seasonal', 'time_limited'])->default('publish')->index();
            
            // Schedule dates
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('expires_at')->nullable()->index();
            
            // Status changes
            $table->string('target_status')->nullable(); // Status to change to
            $table->boolean('is_active')->default(true)->index();
            
            // Flash sale settings
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('sale_percentage')->nullable();
            $table->boolean('restore_original_price')->default(true);
            
            // Recurring schedule
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly, yearly
            $table->json('recurrence_config')->nullable(); // Days of week, months, etc.
            
            // Notification settings
            $table->boolean('send_notification')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            
            // Execution tracking
            $table->timestamp('executed_at')->nullable();
            $table->boolean('execution_success')->default(false);
            $table->text('execution_error')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['scheduled_at', 'is_active']);
            $table->index(['expires_at', 'is_active']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_schedules');
    }
};

