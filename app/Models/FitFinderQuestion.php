<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fit Finder Question Model
 * 
 * Represents a question in a fit finder quiz.
 */
class FitFinderQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'fit_finder_quiz_id',
        'question_text',
        'question_type', // 'single_choice', 'multiple_choice', 'text', 'number'
        'display_order',
        'is_required',
        'help_text',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_required' => 'boolean',
    ];

    /**
     * Quiz this question belongs to.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(FitFinderQuiz::class, 'fit_finder_quiz_id');
    }

    /**
     * Answers/options for this question.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(FitFinderAnswer::class, 'fit_finder_question_id')
            ->orderBy('display_order');
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}

