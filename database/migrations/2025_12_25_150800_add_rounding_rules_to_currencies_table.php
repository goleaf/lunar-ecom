<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds rounding rules to currencies table for automatic price rounding.
     * Rounding modes: none, up, down, nearest, nearest_up, nearest_down
     * Rounding precision: e.g., 0.01 (round to nearest cent), 0.05 (round to nearest 5 cents), 1.00 (round to nearest dollar)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'currencies', function (Blueprint $table) {
            // Rounding mode: none, up, down, nearest, nearest_up, nearest_down
            $table->string('rounding_mode')->default('nearest')->after('decimal_places');
            
            // Rounding precision (e.g., 0.01 for cents, 0.05 for 5-cent increments, 1.00 for whole units)
            $table->decimal('rounding_precision', 10, 4)->default(0.01)->after('rounding_mode');
            
            // Auto-convert prices when currency exchange rate changes
            $table->boolean('auto_convert')->default(false)->after('rounding_precision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'currencies', function (Blueprint $table) {
            $table->dropColumn(['rounding_mode', 'rounding_precision', 'auto_convert']);
        });
    }
};


