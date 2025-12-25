<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Lunar\Models\Product;

class ProductAnswer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_answers';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question_id',
        'answerer_type',
        'answerer_id',
        'answer',
        'answer_original',
        'is_official',
        'is_approved',
        'status',
        'helpful_count',
        'not_helpful_count',
        'moderated_by',
        'moderated_at',
        'moderation_notes',
        'answered_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_official' => 'boolean',
        'is_approved' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'moderated_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($answer) {
            if (!$answer->answered_at) {
                $answer->answered_at = now();
            }
            if (!$answer->answer_original) {
                $answer->answer_original = $answer->answer;
            }
        });

        static::created(function ($answer) {
            // Mark question as answered
            $answer->question->update(['is_answered' => true]);
        });

        static::deleted(function ($answer) {
            // Check if question still has answers
            if ($answer->question->answers()->count() === 0) {
                $answer->question->update(['is_answered' => false]);
            }
        });
    }

    /**
     * Get the question.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ProductQuestion::class, 'question_id');
    }

    /**
     * Get the answerer (polymorphic).
     */
    public function answerer(): MorphTo
    {
        return $this->morphTo('answerer', 'answerer_type', 'answerer_id');
    }

    /**
     * Get the moderator.
     */
    public function moderator(): BelongsTo
    {
        $userClass = class_exists(\Lunar\Models\User::class) 
            ? \Lunar\Models\User::class 
            : \App\Models\User::class;
        
        return $this->belongsTo($userClass, 'moderated_by');
    }

    /**
     * Mark as helpful.
     */
    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Mark as not helpful.
     */
    public function markNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    /**
     * Check if answer is official.
     */
    public function isOfficial(): bool
    {
        return $this->is_official && $this->is_approved;
    }

    /**
     * Scope to get approved answers.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)
            ->where('status', 'approved');
    }

    /**
     * Scope to get official answers.
     */
    public function scopeOfficial($query)
    {
        return $query->where('is_official', true)
            ->where('is_approved', true);
    }

    /**
     * Scope to search answers.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->whereFullText('answer', $search)
            ->orWhere('answer', 'like', "%{$search}%");
    }
}

