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
        Schema::create($this->prefix.'download_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained($this->prefix.'orders')->onDelete('cascade');
            $table->foreignId('order_line_id')->nullable()->constrained($this->prefix.'order_lines')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->onDelete('cascade');
            
            // Download link details
            $table->string('token')->unique()->index(); // Secure download token
            $table->string('email')->index(); // Email address for delivery
            $table->integer('download_count')->default(0);
            $table->integer('download_limit')->nullable(); // Override from digital_products
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_downloaded_at')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('delivered_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id', 'is_active']);
            $table->index(['email', 'is_active']);
            $table->index(['token', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'download_links');
    }
};

