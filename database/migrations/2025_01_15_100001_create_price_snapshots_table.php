<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'price_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('checkout_lock_id')->constrained($this->prefix.'checkout_locks')->onDelete('cascade');
            $table->foreignId('cart_id')->constrained($this->prefix.'carts')->onDelete('cascade');
            $table->foreignId('cart_line_id')->nullable()->constrained($this->prefix.'cart_lines')->onDelete('cascade');
            
            // Price snapshot
            $table->integer('unit_price')->unsigned();
            $table->integer('sub_total')->unsigned();
            $table->integer('discount_total')->default(0)->unsigned();
            $table->integer('tax_total')->unsigned();
            $table->integer('total')->unsigned();
            
            // Discount snapshot
            $table->json('discount_breakdown')->nullable();
            $table->json('applied_discounts')->nullable(); // Store discount IDs and details
            
            // Tax snapshot
            $table->json('tax_breakdown')->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            
            // Currency snapshot
            $table->string('currency_code', 3);
            $table->string('compare_currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 10, 4)->default(1);
            
            // Promotion snapshot
            $table->string('coupon_code')->nullable();
            $table->json('promotion_details')->nullable();
            
            // Timestamps
            $table->timestamp('snapshot_at');
            $table->timestamps();
            
            $table->index(['checkout_lock_id', 'cart_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_snapshots');
    }
};


