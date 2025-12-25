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
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            $table->decimal('popularity_score', 10, 4)->default(0)->index()->after('total_reviews');
            $table->integer('view_count')->default(0)->index()->after('popularity_score');
            $table->integer('order_count')->default(0)->index()->after('view_count');
            $table->timestamp('popularity_updated_at')->nullable()->after('order_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            $table->dropColumn(['popularity_score', 'view_count', 'order_count', 'popularity_updated_at']);
        });
    }
};

