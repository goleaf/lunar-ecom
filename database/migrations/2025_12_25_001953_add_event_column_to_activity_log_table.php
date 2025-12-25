<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventColumnToActivityLogTable extends Migration
{
    public function up()
    {
        $connection = Schema::connection(config('activitylog.database_connection'));
        $tableName = config('activitylog.table_name');
        
        if (!$connection->hasColumn($tableName, 'event')) {
            $connection->table($tableName, function (Blueprint $table) {
                $table->string('event')->nullable()->after('subject_type');
            });
        }
    }

    public function down()
    {
        $connection = Schema::connection(config('activitylog.database_connection'));
        $tableName = config('activitylog.table_name');
        
        if ($connection->hasColumn($tableName, 'event')) {
            $connection->table($tableName, function (Blueprint $table) {
                $table->dropColumn('event');
            });
        }
    }
}
