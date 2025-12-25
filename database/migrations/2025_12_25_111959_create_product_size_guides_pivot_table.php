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
        $prefix = config('lunar.database.table_prefix');
        
        Schema::create('product_size_guides', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('product_id')->constrained($prefix.'products')->cascadeOnDelete();
            $table->foreignId('size_guide_id')->constrained('size_guides')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['product_id', 'size_guide_id']);
            $table->index('product_id');
            $table->index('size_guide_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_size_guides');
    }
};
