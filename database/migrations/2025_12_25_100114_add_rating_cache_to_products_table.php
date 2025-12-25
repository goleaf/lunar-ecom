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
            // Rating cache fields
            $table->decimal('average_rating', 3, 2)->default(0)->index()->after('custom_meta');
            $table->unsignedInteger('total_reviews')->default(0)->index()->after('average_rating');
            $table->unsignedInteger('rating_5_count')->default(0)->after('total_reviews');
            $table->unsignedInteger('rating_4_count')->default(0)->after('rating_5_count');
            $table->unsignedInteger('rating_3_count')->default(0)->after('rating_4_count');
            $table->unsignedInteger('rating_2_count')->default(0)->after('rating_3_count');
            $table->unsignedInteger('rating_1_count')->default(0)->after('rating_2_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            $table->dropColumn([
                'average_rating',
                'total_reviews',
                'rating_5_count',
                'rating_4_count',
                'rating_3_count',
                'rating_2_count',
                'rating_1_count',
            ]);
        });
    }
};
