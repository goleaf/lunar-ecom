<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = $this->prefix.'digital_products';

        if (!Schema::hasColumn($tableName, 'deleted_at')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tableName = $this->prefix.'digital_products';

        if (Schema::hasColumn($tableName, 'deleted_at')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
