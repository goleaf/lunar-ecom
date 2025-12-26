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
        Schema::create($this->prefix.'stock_notification_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_notification_id')->constrained($this->prefix.'stock_notifications')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->cascadeOnDelete();
            
            // Email metrics
            $table->boolean('email_sent')->default(false)->index();
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('email_delivered')->default(false)->index();
            $table->timestamp('email_delivered_at')->nullable();
            $table->boolean('email_opened')->default(false)->index();
            $table->timestamp('email_opened_at')->nullable();
            $table->integer('email_open_count')->default(0);
            
            // Click tracking
            $table->boolean('link_clicked')->default(false)->index();
            $table->timestamp('link_clicked_at')->nullable();
            $table->integer('link_click_count')->default(0);
            $table->string('clicked_link_type')->nullable(); // 'buy_now', 'product_page', 'unsubscribe'
            
            // Conversion tracking
            $table->boolean('converted')->default(false)->index(); // Purchased the product
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes for analytics
            $table->index(['product_variant_id', 'email_sent_at']);
            $table->index(['product_variant_id', 'converted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'stock_notification_metrics');
    }
};


