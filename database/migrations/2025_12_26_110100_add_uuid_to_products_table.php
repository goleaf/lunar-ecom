<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->prefix . 'products', function (Blueprint $table) {
            if (!Schema::hasColumn($this->prefix . 'products', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
        });

        // Backfill UUIDs for existing products.
        DB::table($this->prefix . 'products')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table($this->prefix . 'products')
                        ->where('id', $row->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix . 'products', function (Blueprint $table) {
            if (Schema::hasColumn($this->prefix . 'products', 'uuid')) {
                $table->dropUnique($this->prefix . 'products_uuid_unique');
                $table->dropColumn('uuid');
            }
        });
    }
};
