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
        Schema::create($this->prefix.'product_schedule_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_schedule_id')->nullable()->constrained($this->prefix.'product_schedules')->nullOnDelete();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->string('action')->index(); // published, unpublished, flash_sale_started, flash_sale_ended
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('metadata')->nullable()->comment('Additional data (prices, etc.)');
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->index();
            $table->string('timezone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'executed_at']);
            $table->index(['action', 'executed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_schedule_history');
    }
};

