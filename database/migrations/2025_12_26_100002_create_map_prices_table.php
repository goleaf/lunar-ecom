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
        Schema::create($this->prefix.'map_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained($this->prefix.'currencies')->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained($this->prefix.'channels')->onDelete('cascade');
            
            // MAP pricing
            $table->integer('min_price')->unsigned(); // Minimum Advertised Price in cents
            $table->enum('enforcement_level', ['strict', 'warning'])->default('strict');
            
            // Validity period
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_variant_id', 'currency_id', 'channel_id']);
            $table->index(['valid_from', 'valid_to']);
            $table->index('enforcement_level');
            
            // Unique constraint: one MAP price per variant/currency/channel combination
            $table->unique(['product_variant_id', 'currency_id', 'channel_id'], 'map_price_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'map_prices');
    }
};

