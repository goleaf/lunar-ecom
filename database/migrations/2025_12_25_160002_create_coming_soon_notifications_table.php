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
        Schema::create($this->prefix.'coming_soon_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->string('email')->index();
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->nullOnDelete();
            $table->string('token')->unique()->index();
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'email']);
            $table->index(['product_id', 'notified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'coming_soon_notifications');
    }
};


