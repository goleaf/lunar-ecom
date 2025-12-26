<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->cascadeOnDelete();
            $table->foreignId('price_matrix_id')->nullable()->constrained($this->prefix.'price_matrices')->nullOnDelete();
            
            // Price change details
            $table->decimal('old_price', 15, 2)->nullable();
            $table->decimal('new_price', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            
            // Change context
            $table->enum('change_type', ['manual', 'matrix', 'import', 'bulk', 'scheduled'])->default('manual')->index();
            $table->string('change_reason')->nullable();
            $table->text('change_notes')->nullable();
            
            // Context (JSON) - Store conditions that applied
            // {
            //   "quantity": 10,
            //   "customer_group": "wholesale",
            //   "region": "US"
            // }
            $table->json('context')->nullable();
            
            // User tracking
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'product_variant_id', 'changed_at']);
            $table->index(['price_matrix_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_history');
    }
};


