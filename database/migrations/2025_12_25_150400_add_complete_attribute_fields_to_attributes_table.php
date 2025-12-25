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
        // Add sortable column if it doesn't exist
        if (!Schema::hasColumn($this->prefix.'attributes', 'sortable')) {
            Schema::table($this->prefix.'attributes', function (Blueprint $table) {
                $table->boolean('sortable')->default(false)->index()->after('filterable');
            });
        }
        
        // Validation rules already exists from earlier migration, skip adding it
        // (It was added as string in 2022_01_12_100000_add_columns_to_attributes_table)
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

