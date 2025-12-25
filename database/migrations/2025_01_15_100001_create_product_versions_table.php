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
        Schema::create($this->prefix.'product_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->cascadeOnDelete();
            
            // Version metadata
            $table->unsignedInteger('version_number')->index();
            $table->string('version_name')->nullable();
            $table->text('version_notes')->nullable();
            
            // Snapshot of product data at this version
            $table->json('product_data')->nullable(); // Full product state snapshot
            
            // Who created this version
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'version_number']);
            $table->unique(['product_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_versions');
    }
};

