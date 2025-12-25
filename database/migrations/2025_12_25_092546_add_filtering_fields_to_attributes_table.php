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
        Schema::table($this->prefix.'attributes', function (Blueprint $table) {
            // Unit field (e.g., kg, cm, inches, etc.)
            $table->string('unit')->nullable()->after('filterable');
            
            // Display order for sorting attributes
            $table->integer('display_order')->default(0)->index()->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'attributes', function (Blueprint $table) {
            $table->dropColumn(['unit', 'display_order']);
        });
    }
};
