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
            // Add publish_at and unpublish_at if not already exist
            if (!Schema::hasColumn($this->prefix.'products', 'publish_at')) {
                $table->timestamp('publish_at')->nullable()->after('scheduled_unpublish_at')->index();
            }
            if (!Schema::hasColumn($this->prefix.'products', 'unpublish_at')) {
                $table->timestamp('unpublish_at')->nullable()->after('publish_at')->index();
            }
            
            // Coming Soon state
            $table->boolean('is_coming_soon')->default(false)->index()->after('status');
            $table->text('coming_soon_message')->nullable()->after('is_coming_soon');
            $table->timestamp('expected_available_at')->nullable()->after('coming_soon_message')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            $table->dropColumn([
                'publish_at',
                'unpublish_at',
                'is_coming_soon',
                'coming_soon_message',
                'expected_available_at',
            ]);
        });
    }
};


