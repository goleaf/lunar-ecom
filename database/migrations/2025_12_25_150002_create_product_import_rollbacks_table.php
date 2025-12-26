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
        Schema::create($this->prefix.'product_import_rollbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_import_id')->constrained($this->prefix.'product_imports')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained($this->prefix.'products')->cascadeOnDelete();
            $table->json('original_data')->nullable(); // Snapshot of product before import
            $table->string('action')->index(); // created, updated
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
            
            $table->index(['product_import_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_import_rollbacks');
    }
};


