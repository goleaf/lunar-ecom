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
        Schema::create('fit_finder_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fit_finder_quiz_id')->constrained('fit_finder_quizzes')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', ['single_choice', 'multiple_choice', 'text', 'number'])->default('single_choice');
            $table->integer('display_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->text('help_text')->nullable();
            $table->timestamps();
            
            $table->index(['fit_finder_quiz_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_finder_questions');
    }
};
