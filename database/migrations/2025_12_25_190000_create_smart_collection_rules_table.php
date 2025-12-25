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
        Schema::create($this->prefix.'smart_collection_rules', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('collection_id')
                ->constrained($this->prefix.'collections')
                ->cascadeOnDelete();
            
            // Rule configuration
            $table->string('field')->index(); // price, tag, product_type, inventory_status, brand, category, attribute
            $table->string('operator')->index(); // equals, not_equals, greater_than, less_than, contains, in, not_in, is_null, is_not_null, between
            $table->json('value')->nullable(); // Can be single value or array
            
            // Rule grouping (for AND/OR logic)
            $table->string('logic_group')->nullable()->index(); // Group rules together
            $table->enum('group_operator', ['and', 'or'])->default('and'); // How groups are combined
            
            // Rule priority/order
            $table->unsignedInteger('priority')->default(0)->index();
            
            // Rule metadata
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['collection_id', 'is_active', 'priority']);
            $table->index(['collection_id', 'logic_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'smart_collection_rules');
    }
};

