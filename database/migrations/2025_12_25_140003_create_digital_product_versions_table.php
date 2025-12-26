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
        Schema::create($this->prefix.'digital_product_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_product_id')->constrained($this->prefix.'digital_products')->cascadeOnDelete();
            $table->string('version')->index();
            $table->string('file_path')->comment('Encrypted file path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('original_filename')->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->boolean('notify_customers')->default(false)->comment('Send update notification to existing customers');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            
            $table->unique(['digital_product_id', 'version']);
            $table->index(['digital_product_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'digital_product_versions');
    }
};


