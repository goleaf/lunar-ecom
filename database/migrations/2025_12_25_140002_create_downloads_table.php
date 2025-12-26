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
        Schema::create($this->prefix.'downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->nullOnDelete();
            $table->foreignId('order_id')->constrained($this->prefix.'orders')->cascadeOnDelete();
            $table->foreignId('digital_product_id')->constrained($this->prefix.'digital_products')->cascadeOnDelete();
            $table->string('download_token')->unique()->index();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('first_downloaded_at')->nullable();
            $table->timestamp('last_downloaded_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('license_key')->nullable()->index()->comment('Generated license key for software products');
            $table->boolean('license_key_sent')->default(false);
            $table->timestamps();
            
            $table->index(['customer_id', 'order_id']);
            $table->index(['digital_product_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'downloads');
    }
};


