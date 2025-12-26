<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'discounts', function (Blueprint $table) {
            // Stacking configuration
            $table->string('stacking_mode')->nullable()->after('stop')->index();
            $table->string('stacking_strategy')->nullable()->after('stacking_mode')->index();
            $table->integer('max_discount_cap')->nullable()->after('stacking_strategy');
            
            // Compliance and audit fields
            $table->boolean('map_protected')->default(false)->after('max_discount_cap')->index();
            $table->boolean('b2b_contract')->default(false)->after('map_protected')->index();
            $table->boolean('manual_override_auto')->default(true)->after('b2b_contract');
            $table->string('jurisdiction')->nullable()->after('manual_override_auto')->index();
            
            // Price tracking
            $table->boolean('track_price_before_discount')->default(true)->after('jurisdiction');
            $table->boolean('log_discount_reason')->default(true)->after('track_price_before_discount');
            $table->boolean('require_audit_trail')->default(false)->after('log_discount_reason');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'discounts', function (Blueprint $table) {
            $table->dropColumn([
                'stacking_mode',
                'stacking_strategy',
                'max_discount_cap',
                'map_protected',
                'b2b_contract',
                'manual_override_auto',
                'jurisdiction',
                'track_price_before_discount',
                'log_discount_reason',
                'require_audit_trail',
            ]);
        });
    }
};


