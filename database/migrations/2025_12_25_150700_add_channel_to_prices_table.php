<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds channel_id to prices table for channel-specific pricing.
     * Allows different prices per channel (e.g., different prices for web vs mobile app).
     */
    public function up(): void
    {
        Schema::table($this->prefix.'prices', function (Blueprint $table) {
            // Channel-specific pricing
            $table->foreignId('channel_id')->nullable()->after('currency_id')
                ->constrained($this->prefix.'channels')->onDelete('cascade');
            
            // Index for efficient channel-based price queries
            $table->index(['priceable_type', 'priceable_id', 'channel_id', 'currency_id', 'customer_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'prices', function (Blueprint $table) {
            $table->dropIndex(['priceable_type', 'priceable_id', 'channel_id', 'currency_id', 'customer_group_id']);
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id');
        });
    }
};

