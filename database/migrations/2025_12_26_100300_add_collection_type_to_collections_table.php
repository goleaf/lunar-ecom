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
        // Column already exists from 2025_12_25_170000_add_collection_management_fields migration
        if (!Schema::hasColumn($this->prefix.'collections', 'collection_type')) {
            Schema::table($this->prefix.'collections', function (Blueprint $table) {
                $table->string('collection_type', 50)
                      ->default('standard')
                      ->after('sort')
                      ->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'collections', function (Blueprint $table) {
            $table->dropColumn('collection_type');
        });
    }
};

