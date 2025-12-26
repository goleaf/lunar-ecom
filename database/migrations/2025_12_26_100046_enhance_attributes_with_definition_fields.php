<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances attributes table with:
     * - Code (unique identifier)
     * - Scope (product/variant)
     * - Localizable flag
     * - Channel-specific flag
     * - Required flag
     * - Default value
     * - UI hint (dropdown, swatch, slider, etc.)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'attributes', function (Blueprint $table) {
            // Code (unique identifier, alternative to handle)
            if (!Schema::hasColumn($this->prefix.'attributes', 'code')) {
                $table->string('code')->unique()->nullable()->after('handle');
            }

            // Scope: product or variant
            if (!Schema::hasColumn($this->prefix.'attributes', 'scope')) {
                $table->enum('scope', ['product', 'variant', 'both'])->default('product')->after('code');
            }

            // Localizable flag
            if (!Schema::hasColumn($this->prefix.'attributes', 'localizable')) {
                $table->boolean('localizable')->default(false)->after('scope');
            }

            // Channel-specific flag
            if (!Schema::hasColumn($this->prefix.'attributes', 'channel_specific')) {
                $table->boolean('channel_specific')->default(false)->after('localizable');
            }

            // Required flag
            if (!Schema::hasColumn($this->prefix.'attributes', 'required')) {
                $table->boolean('required')->default(false)->after('channel_specific');
            }

            // Default value (JSON to support different types)
            if (!Schema::hasColumn($this->prefix.'attributes', 'default_value')) {
                $table->json('default_value')->nullable()->after('required');
            }

            // UI hint (dropdown, swatch, slider, text, textarea, etc.)
            if (!Schema::hasColumn($this->prefix.'attributes', 'ui_hint')) {
                $table->string('ui_hint')->nullable()->after('default_value');
            }

            // Enhanced validation rules (JSON)
            if (!Schema::hasColumn($this->prefix.'attributes', 'validation_rules')) {
                $table->json('validation_rules')->nullable()->after('ui_hint');
            }

            // Indexes
            $table->index('code');
            $table->index('scope');
            $table->index(['localizable', 'channel_specific']);
            $table->index('required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'attributes', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'scope',
                'localizable',
                'channel_specific',
                'required',
                'default_value',
                'ui_hint',
                'validation_rules',
            ]);
        });
    }
};


