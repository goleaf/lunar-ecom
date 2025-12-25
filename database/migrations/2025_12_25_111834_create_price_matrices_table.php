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
        Schema::create($this->prefix.'price_matrices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->onDelete('cascade');
            $table->string('matrix_type')->index(); // quantity, customer_group, region, mixed
            $table->json('rules'); // JSON structure for pricing rules
            $table->dateTime('starts_at')->nullable(); // Promotional pricing start
            $table->dateTime('ends_at')->nullable(); // Promotional pricing end
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->index(); // Higher priority rules apply first
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'matrix_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_matrices');
    }
};
