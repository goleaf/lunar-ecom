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
        Schema::create($this->prefix.'availability_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->nullOnDelete();
            $table->foreignId('order_line_id')->nullable()->constrained($this->prefix.'order_lines')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->nullOnDelete();
            
            // Booking dates
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index(); // For multi-day bookings
            $table->time('start_time')->nullable(); // For hourly bookings
            $table->time('end_time')->nullable();
            
            // Quantity
            $table->integer('quantity')->default(1);
            
            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending')->index();
            
            // Pricing
            $table->decimal('total_price', 15, 2)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->integer('duration_days')->nullable(); // Rental duration
            $table->enum('pricing_type', ['daily', 'weekly', 'monthly', 'fixed'])->default('daily');
            
            // Customer information
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Timezone
            $table->string('timezone')->default('UTC');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'start_date', 'status']);
            $table->index(['start_date', 'end_date', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'availability_bookings');
    }
};


