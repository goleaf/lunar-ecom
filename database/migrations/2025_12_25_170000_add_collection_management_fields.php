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
            // Collection type
            $table->string('collection_type')->default('manual')->index(); // manual, bestsellers, new_arrivals, featured, seasonal, custom
            
            // Automatic assignment settings
            $table->boolean('auto_assign')->default(false)->index();
            $table->json('assignment_rules')->nullable(); // Rules for automatic product assignment
            $table->integer('max_products')->nullable(); // Maximum products in collection
            $table->string('sort_by')->default('created_at'); // created_at, price, name, popularity, sales_count, rating
            $table->string('sort_direction')->default('desc'); // asc, desc
            
            // Display settings
            $table->boolean('show_on_homepage')->default(false)->index();
            $table->integer('homepage_position')->nullable();
            $table->string('display_style')->default('grid'); // grid, list, carousel
            $table->integer('products_per_row')->default(4);
            
            // Schedule settings
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Statistics
            $table->integer('product_count')->default(0);
            $table->timestamp('last_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'collections', function (Blueprint $table) {
            $table->dropColumn([
                'collection_type',
                'auto_assign',
                'assignment_rules',
                'max_products',
                'sort_by',
                'sort_direction',
                'show_on_homepage',
                'homepage_position',
                'display_style',
                'products_per_row',
                'starts_at',
                'ends_at',
                'product_count',
                'last_updated_at',
            ]);
        });
    }
};

