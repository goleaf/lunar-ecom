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
        Schema::create($this->prefix.'product_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained($this->prefix.'product_imports')->onDelete('cascade');
            
            // Error details
            $table->integer('row_number')->index(); // Row number in CSV (1-based)
            $table->string('field')->nullable(); // Field that caused the error
            $table->text('error_message');
            $table->string('error_type')->nullable()->index(); // validation, duplicate, missing, etc.
            $table->json('row_data')->nullable(); // The actual row data that caused the error
            
            $table->timestamps();
            
            // Indexes
            $table->index(['import_id', 'row_number']);
            $table->index(['import_id', 'error_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_import_errors');
    }
};

