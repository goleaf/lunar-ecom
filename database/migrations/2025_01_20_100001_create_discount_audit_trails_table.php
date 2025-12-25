<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'discount_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained($this->prefix.'discounts')->onDelete('cascade');
            $table->foreignId('cart_id')->nullable()->constrained($this->prefix.'carts')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Discount application details
            $table->string('discount_type')->index();
            $table->string('stacking_mode')->nullable();
            $table->string('stacking_strategy')->nullable();
            $table->integer('priority')->default(1);
            
            // Price tracking
            $table->integer('price_before_discount')->nullable();
            $table->integer('discount_amount');
            $table->integer('price_after_discount');
            
            // Application context
            $table->string('scope')->index(); // item, cart, shipping, payment
            $table->text('reason')->nullable(); // Why this discount was applied
            $table->text('conflict_resolution')->nullable(); // How conflicts were resolved
            $table->json('applied_with')->nullable(); // Other discounts applied together
            
            // Compliance
            $table->string('jurisdiction')->nullable();
            $table->boolean('map_protected')->default(false);
            $table->boolean('b2b_contract')->default(false);
            $table->boolean('manual_coupon')->default(false);
            
            // Metadata
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['discount_id', 'created_at']);
            $table->index(['cart_id', 'created_at']);
            $table->index(['order_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'discount_audit_trails');
    }
};

