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
            $table->string('collection_type', 50)
                  ->default('standard')
                  ->after('sort')
                  ->index();
        });
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

