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
        Schema::create('fit_finder_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fit_finder_question_id')->constrained('fit_finder_questions')->cascadeOnDelete();
            $table->string('answer_text');
            $table->string('answer_value')->nullable(); // Value used in recommendation logic
            $table->integer('display_order')->default(0);
            $table->json('size_adjustment')->nullable(); // Size adjustments based on this answer
            $table->timestamps();
            
            $table->index(['fit_finder_question_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_finder_answers');
    }
};
