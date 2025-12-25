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
        Schema::table($this->prefix.'carts', function (Blueprint $table) {
            // Cart pricing metadata fields
            $table->json('pricing_snapshot')->nullable()->after('meta');
            $table->timestamp('last_reprice_at')->nullable()->after('pricing_snapshot');
            $table->integer('pricing_version')->default(0)->unsigned()->after('last_reprice_at');
            $table->boolean('requires_reprice')->default(false)->after('pricing_version');
            
            // Indexes
            $table->index('last_reprice_at');
            $table->index('requires_reprice');
            $table->index('pricing_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'carts', function (Blueprint $table) {
            $table->dropIndex([$this->prefix.'carts_last_reprice_at_index']);
            $table->dropIndex([$this->prefix.'carts_requires_reprice_index']);
            $table->dropIndex([$this->prefix.'carts_pricing_version_index']);
            
            $table->dropColumn([
                'pricing_snapshot',
                'last_reprice_at',
                'pricing_version',
                'requires_reprice',
            ]);
        });
    }
};

