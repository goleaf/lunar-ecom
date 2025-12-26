<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product;
use Lunar\Models\Customer;

class ProductQuestion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_questions';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'customer_id',
        'customer_name',
        'email',
        'question',
        'question_original',
        'status',
        'is_public',
        'is_answered',
        'views_count',
        'helpful_count',
        'not_helpful_count',
        'moderated_by',
        'moderated_at',
        'moderation_notes',
        'asked_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_answered' => 'boolean',
        'views_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'moderated_at' => 'datetime',
        'asked_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($question) {
            if (!$question->asked_at) {
                $question->asked_at = now();
            }
            if (!$question->question_original) {
                $question->question_original = $question->question;
            }
        });
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the answers.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ProductAnswer::class, 'question_id')
            ->where('is_approved', true)
            ->orderBy('is_official', 'desc')
            ->orderBy('answered_at', 'asc');
    }

    /**
     * Get all answers (including unapproved).
     */
    public function allAnswers(): HasMany
    {
        return $this->hasMany(ProductAnswer::class, 'question_id')
            ->orderBy('is_official', 'desc')
            ->orderBy('answered_at', 'asc');
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
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
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
     * Check if question is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->is_public;
    }

    /**
     * Check if question is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope to get approved questions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')
            ->where('is_public', true);
    }

    /**
     * Scope to get pending questions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get answered questions.
     */
    public function scopeAnswered($query)
    {
        return $query->where('is_answered', true);
    }

    /**
     * Scope to get unanswered questions.
     */
    public function scopeUnanswered($query)
    {
        return $query->where('is_answered', false);
    }

    /**
     * Scope to search questions.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->whereFullText('question', $search)
            ->orWhere('question', 'like', "%{$search}%");
    }
}


