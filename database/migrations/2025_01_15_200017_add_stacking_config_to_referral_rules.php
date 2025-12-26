<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_rules', function (Blueprint $table) {
            $table->decimal('max_total_discount_percent', 5, 2)->nullable()->after('stacking_mode');
            $table->decimal('max_total_discount_amount', 10, 2)->nullable()->after('max_total_discount_percent');
            $table->boolean('apply_before_tax')->default(true)->after('max_total_discount_amount');
            $table->boolean('shipping_discount_stacks')->default(false)->after('apply_before_tax');
        });
    }

    public function down(): void
    {
        Schema::table('referral_rules', function (Blueprint $table) {
            $table->dropColumn([
                'max_total_discount_percent',
                'max_total_discount_amount',
                'apply_before_tax',
                'shipping_discount_stacks',
            ]);
        });
    }
};


