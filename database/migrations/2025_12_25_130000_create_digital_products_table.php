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
        Schema::create($this->prefix.'digital_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->unique()->constrained($this->prefix.'product_variants')->onDelete('cascade');
            
            // Digital product settings
            $table->boolean('is_digital')->default(true)->index();
            $table->integer('download_limit')->nullable(); // null = unlimited
            $table->integer('download_expiry_days')->nullable(); // null = never expires
            $table->boolean('require_login')->default(true); // Require customer to be logged in
            
            // File storage
            $table->string('storage_disk')->default('local'); // local, s3, etc.
            $table->text('file_path')->nullable(); // Path to the file
            $table->string('file_name')->nullable(); // Original filename
            $table->integer('file_size')->nullable(); // File size in bytes
            $table->string('file_type')->nullable(); // MIME type
            
            // Delivery settings
            $table->boolean('auto_deliver')->default(true); // Automatically deliver after purchase
            $table->boolean('send_email')->default(true); // Send email with download link
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'digital_products');
    }
};

