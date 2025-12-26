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
        Schema::create($this->prefix.'product_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->cascadeOnDelete();
            
            // Availability type
            $table->enum('availability_type', ['date_range', 'specific_dates', 'recurring', 'always_available'])->default('always_available')->index();
            
            // Date range availability
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            
            // Specific dates (JSON array of dates)
            $table->json('available_dates')->nullable(); // ['2025-01-01', '2025-01-02']
            $table->json('unavailable_dates')->nullable(); // Blackout dates
            
            // Recurring pattern
            $table->boolean('is_recurring')->default(false)->index();
            $table->json('recurrence_pattern')->nullable(); // {
            //   "type": "weekly",
            //   "days": [1, 2, 3, 4, 5], // Monday-Friday
            //   "interval": 1, // Every week
            //   "end_date": "2025-12-31" // Optional end date
            // }
            
            // Quantity limits
            $table->integer('max_quantity_per_date')->nullable(); // Max bookings per date
            $table->integer('total_quantity')->nullable(); // Total available quantity
            
            // Time settings (for hourly bookings)
            $table->time('available_from')->nullable(); // Start time
            $table->time('available_until')->nullable(); // End time
            $table->integer('slot_duration_minutes')->nullable(); // Duration of each slot
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->string('timezone')->default('UTC')->index();
            
            // Priority (for multiple availability rules)
            $table->integer('priority')->default(0)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_active', 'start_date', 'end_date']);
            $table->index(['product_variant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_availability');
    }
};


