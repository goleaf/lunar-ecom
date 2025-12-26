<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances attribute value tables with:
     * - Per-locale values
     * - Change history tracking
     * - Better typed storage
     */
    public function up(): void
    {
        $prefix = $this->prefix;

        // Enhance product_attribute_values
        Schema::table($prefix.'product_attribute_values', function (Blueprint $table) {
            if (!Schema::hasColumn($prefix.'product_attribute_values', 'locale')) {
                $table->string('locale', 10)->nullable()->after('attribute_id')->index();
            }
            if (!Schema::hasColumn($prefix.'product_attribute_values', 'is_override')) {
                $table->boolean('is_override')->default(false)->after('text_value');
            }
            if (!Schema::hasColumn($prefix.'product_attribute_values', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn($prefix.'product_attribute_values', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            }

            // Update unique constraint to include locale
            $table->dropUnique(['product_id', 'attribute_id']);
            $table->unique(['product_id', 'attribute_id', 'locale'], 'product_attr_locale_unique');
        });

        // Enhance variant_attribute_values
        Schema::table($prefix.'variant_attribute_values', function (Blueprint $table) {
            if (!Schema::hasColumn($prefix.'variant_attribute_values', 'locale')) {
                $table->string('locale', 10)->nullable()->after('attribute_id')->index();
            }
            if (!Schema::hasColumn($prefix.'variant_attribute_values', 'is_override')) {
                $table->boolean('is_override')->default(false)->after('text_value');
            }
            if (!Schema::hasColumn($prefix.'variant_attribute_values', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn($prefix.'variant_attribute_values', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            }

            // Update unique constraint to include locale
            $table->dropUnique(['product_variant_id', 'attribute_id']);
            $table->unique(['product_variant_id', 'attribute_id', 'locale'], 'variant_attr_locale_unique');
        });

        // Enhance channel_attribute_values
        Schema::table($prefix.'channel_attribute_values', function (Blueprint $table) {
            if (!Schema::hasColumn($prefix.'channel_attribute_values', 'locale')) {
                $table->string('locale', 10)->nullable()->after('attribute_id')->index();
            }
            if (!Schema::hasColumn($prefix.'channel_attribute_values', 'is_override')) {
                $table->boolean('is_override')->default(false)->after('text_value');
            }
            if (!Schema::hasColumn($prefix.'channel_attribute_values', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn($prefix.'channel_attribute_values', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            }

            // Update unique constraint to include locale
            $table->dropUnique(['product_id', 'channel_id', 'attribute_id']);
            $table->unique(['product_id', 'channel_id', 'attribute_id', 'locale'], 'channel_attr_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = $this->prefix;

        Schema::table($prefix.'product_attribute_values', function (Blueprint $table) {
            $table->dropUnique('product_attr_locale_unique');
            $table->unique(['product_id', 'attribute_id']);
            $table->dropColumn(['locale', 'is_override', 'created_by', 'updated_by']);
        });

        Schema::table($prefix.'variant_attribute_values', function (Blueprint $table) {
            $table->dropUnique('variant_attr_locale_unique');
            $table->unique(['product_variant_id', 'attribute_id']);
            $table->dropColumn(['locale', 'is_override', 'created_by', 'updated_by']);
        });

        Schema::table($prefix.'channel_attribute_values', function (Blueprint $table) {
            $table->dropUnique('channel_attr_locale_unique');
            $table->unique(['product_id', 'channel_id', 'attribute_id']);
            $table->dropColumn(['locale', 'is_override', 'created_by', 'updated_by']);
        });
    }
};


