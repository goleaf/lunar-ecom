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
        
        Schema::create('product_fit_finder_quizzes', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('product_id')->constrained($prefix.'products')->cascadeOnDelete();
            $table->foreignId('fit_finder_quiz_id')->constrained('fit_finder_quizzes')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['product_id', 'fit_finder_quiz_id']);
            $table->index('product_id');
            $table->index('fit_finder_quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_fit_finder_quizzes');
    }
};
