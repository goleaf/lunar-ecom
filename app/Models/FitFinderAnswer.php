<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fit Finder Answer Model
 * 
 * Represents an answer/option for a fit finder question.
 */
class FitFinderAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'fit_finder_question_id',
        'answer_text',
        'answer_value', // Value used in recommendation logic
        'display_order',
        'size_adjustment', // JSON field for size adjustments based on this answer
    ];

    protected $casts = [
        'display_order' => 'integer',
        'size_adjustment' => 'array',
    ];

    /**
     * Question this answer belongs to.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(FitFinderQuestion::class, 'fit_finder_question_id');
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}

