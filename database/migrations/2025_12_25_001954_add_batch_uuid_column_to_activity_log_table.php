<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchUuidColumnToActivityLogTable extends Migration
{
    public function up()
    {
        $connection = Schema::connection(config('activitylog.database_connection'));
        $tableName = config('activitylog.table_name');
        
        if (!$connection->hasColumn($tableName, 'batch_uuid')) {
            $connection->table($tableName, function (Blueprint $table) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            });
        }
    }

    public function down()
    {
        $connection = Schema::connection(config('activitylog.database_connection'));
        $tableName = config('activitylog.table_name');
        
        if ($connection->hasColumn($tableName, 'batch_uuid')) {
            $connection->table($tableName, function (Blueprint $table) {
                $table->dropColumn('batch_uuid');
            });
        }
    }
}
