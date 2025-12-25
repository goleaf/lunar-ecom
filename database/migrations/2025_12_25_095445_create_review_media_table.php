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
        Schema::create($this->prefix.'review_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained($this->prefix.'reviews')->onDelete('cascade');
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
            
            // Note: Actual media files will be stored using Spatie Media Library
            // This table is for additional metadata if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'review_media');
    }
};
