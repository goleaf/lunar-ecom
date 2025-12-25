<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds complete attribute system fields:
     * - sortable: Whether attribute can be used for sorting
     * - validation_rules: JSON field for custom validation rules per attribute
     */
    public function up(): void
    {
        Schema::table($this->prefix.'attributes', function (Blueprint $table) {
            // Sortable flag (for attributes that can be used to sort products)
            $table->boolean('sortable')->default(false)->index()->after('filterable');
            
            // Validation rules as JSON (e.g., {"min": 0, "max": 100, "pattern": "...", "required": true})
            $table->json('validation_rules')->nullable()->after('configuration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'attributes', function (Blueprint $table) {
            $table->dropColumn(['sortable', 'validation_rules']);
        });
    }
};

