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
        Schema::create($this->prefix.'availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            
            // Rule type
            $table->enum('rule_type', [
                'minimum_rental_period',
                'maximum_rental_period',
                'lead_time',
                'buffer_time',
                'cancellation_policy',
                'blackout_date',
                'special_pricing',
            ])->index();
            
            // Rule configuration (JSON)
            $table->json('rule_config')->nullable(); // {
            //   "minimum_days": 3,
            //   "maximum_days": 30,
            //   "lead_time_hours": 24,
            //   "buffer_hours": 2,
            //   "cancellation_hours": 48,
            //   "refund_percentage": 80
            // }
            
            // Date range for rule (optional)
            $table->date('rule_start_date')->nullable();
            $table->date('rule_end_date')->nullable();
            
            // Priority
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'availability_rules');
    }
};


