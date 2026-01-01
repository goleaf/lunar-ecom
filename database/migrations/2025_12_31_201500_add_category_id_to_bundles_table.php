<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix . 'bundles', function (Blueprint $table) {
            if (Schema::hasColumn($this->prefix . 'bundles', 'category_id')) {
                return;
            }

            $table->foreignId('category_id')
                ->nullable()
                ->constrained($this->prefix . 'categories')
                ->nullOnDelete()
                ->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix . 'bundles', function (Blueprint $table) {
            if (! Schema::hasColumn($this->prefix . 'bundles', 'category_id')) {
                return;
            }

            $table->dropConstrainedForeignId('category_id');
        });
    }
};

