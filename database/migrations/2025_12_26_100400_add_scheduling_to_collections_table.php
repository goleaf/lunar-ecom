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
        Schema::table($this->prefix.'collections', function (Blueprint $table) {
            $table->datetime('scheduled_publish_at')->nullable()->after('collection_type')->index();
            $table->datetime('scheduled_unpublish_at')->nullable()->after('scheduled_publish_at')->index();
            $table->boolean('auto_publish_products')->default(true)->after('scheduled_unpublish_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'collections', function (Blueprint $table) {
            $table->dropColumn(['scheduled_publish_at', 'scheduled_unpublish_at', 'auto_publish_products']);
        });
    }
};

