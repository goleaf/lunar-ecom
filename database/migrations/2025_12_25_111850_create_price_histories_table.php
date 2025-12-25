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
        Schema::create($this->prefix.'price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained($this->prefix.'product_variants')->onDelete('cascade');
            $table->foreignId('price_matrix_id')->nullable()->constrained($this->prefix.'price_matrices')->onDelete('set null');
            $table->foreignId('currency_id')->constrained($this->prefix.'currencies');
            $table->foreignId('customer_group_id')->nullable()->constrained($this->prefix.'customer_groups')->onDelete('set null');
            $table->string('region')->nullable(); // Country/region code
            $table->integer('old_price')->nullable(); // Price before change (in cents)
            $table->integer('new_price'); // New price (in cents)
            $table->integer('quantity_tier')->nullable(); // Quantity tier if applicable
            $table->string('change_type'); // created, updated, deleted, approved, rejected
            $table->text('change_reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['product_id', 'variant_id', 'created_at']);
            $table->index(['price_matrix_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_histories');
    }
};
