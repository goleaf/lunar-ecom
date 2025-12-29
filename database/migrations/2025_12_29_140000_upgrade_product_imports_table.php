<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = $this->prefix.'product_imports';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'original_filename')) {
                $table->string('original_filename')->nullable()->after('file_name');
            }

            if (! Schema::hasColumn($tableName, 'file_type')) {
                $table->string('file_type', 10)->nullable()->after('file_size');
            }

            if (! Schema::hasColumn($tableName, 'skipped_rows')) {
                $table->integer('skipped_rows')->default(0)->after('failed_rows');
            }

            if (! Schema::hasColumn($tableName, 'validation_errors')) {
                $table->json('validation_errors')->nullable()->after('options');
            }

            if (! Schema::hasColumn($tableName, 'import_report')) {
                $table->json('import_report')->nullable()->after('error_summary');
            }

            if (! Schema::hasColumn($tableName, 'error_message')) {
                $table->text('error_message')->nullable()->after('import_report');
            }
        });
    }

    public function down(): void
    {
        $tableName = $this->prefix.'product_imports';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            foreach (['original_filename', 'file_type', 'skipped_rows', 'validation_errors', 'import_report', 'error_message'] as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

