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
        Schema::table($this->prefix.'cart_lines', function (Blueprint $table) {
            // Pricing metadata fields
            $table->integer('original_unit_price')->nullable()->unsigned()->after('quantity');
            $table->integer('final_unit_price')->nullable()->unsigned()->after('original_unit_price');
            $table->json('discount_breakdown')->nullable()->after('final_unit_price');
            $table->integer('tax_base')->nullable()->unsigned()->after('discount_breakdown');
            $table->json('applied_rules')->nullable()->after('tax_base');
            $table->string('price_source')->nullable()->after('applied_rules'); // 'base', 'contract', 'promo', 'matrix'
            $table->timestamp('price_calculated_at')->nullable()->after('price_source');
            $table->string('price_hash', 64)->nullable()->after('price_calculated_at');
            
            // Indexes
            $table->index('price_calculated_at');
            $table->index('price_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'cart_lines', function (Blueprint $table) {
            $table->dropIndex([$this->prefix.'cart_lines_price_calculated_at_index']);
            $table->dropIndex([$this->prefix.'cart_lines_price_source_index']);
            
            $table->dropColumn([
                'original_unit_price',
                'final_unit_price',
                'discount_breakdown',
                'tax_base',
                'applied_rules',
                'price_source',
                'price_calculated_at',
                'price_hash',
            ]);
        });
    }
};

