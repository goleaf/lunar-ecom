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
        Schema::create($this->prefix.'product_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_import_id')->constrained($this->prefix.'product_imports')->cascadeOnDelete();
            $table->unsignedInteger('row_number')->index();
            $table->string('status')->default('pending')->index(); // pending, success, failed, skipped
            $table->json('raw_data')->nullable(); // Original row data
            $table->json('mapped_data')->nullable(); // Mapped data after field mapping
            $table->json('validation_errors')->nullable();
            $table->foreignId('product_id')->nullable()->constrained($this->prefix.'products')->nullOnDelete();
            $table->string('sku')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->text('success_message')->nullable();
            $table->timestamps();
            
            $table->index(['product_import_id', 'row_number']);
            $table->index(['product_import_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_import_rows');
    }
};


