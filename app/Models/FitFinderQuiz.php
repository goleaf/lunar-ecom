<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product;

/**
 * Fit Finder Quiz Model
 * 
 * Represents an interactive quiz that helps customers find their perfect fit.
 * Contains questions and logic to recommend sizes.
 */
class FitFinderQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_type', // e.g., 'clothing', 'shoes', 'accessories'
        'gender', // 'men', 'women', 'unisex', 'kids'
        'is_active',
        'display_order',
        'size_guide_id', // Associated size guide
        'recommendation_logic', // JSON field for size recommendation rules
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'recommendation_logic' => 'array',
    ];

    /**
     * Size guide associated with this quiz.
     */
    public function sizeGuide()
    {
        return $this->belongsTo(SizeGuide::class, 'size_guide_id');
    }

    /**
     * Questions in this quiz.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(FitFinderQuestion::class, 'fit_finder_quiz_id')
            ->orderBy('display_order');
    }

    /**
     * Products that use this fit finder quiz.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_fit_finder_quizzes',
            'fit_finder_quiz_id',
            'product_id'
        )->withTimestamps();
    }

    /**
     * Fit feedback entries for this quiz.
     */
    public function fitFeedbacks(): HasMany
    {
        return $this->hasMany(FitFeedback::class, 'fit_finder_quiz_id');
    }

    /**
     * Scope to get active quizzes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category type.
     */
    public function scopeByCategoryType($query, string $categoryType)
    {
        return $query->where('category_type', $categoryType);
    }

    /**
     * Scope to filter by gender.
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Calculate recommended size based on answers.
     */
    public function calculateRecommendedSize(array $answers): ?string
    {
        $logic = $this->recommendation_logic ?? [];
        
        if (empty($logic)) {
            return null;
        }

        // Simple rule-based recommendation
        // This can be extended with more complex logic
        foreach ($logic as $rule) {
            if ($this->matchesRule($rule, $answers)) {
                return $rule['recommended_size'] ?? null;
            }
        }

        return null;
    }

    /**
     * Check if answers match a rule.
     */
    protected function matchesRule(array $rule, array $answers): bool
    {
        if (!isset($rule['conditions'])) {
            return false;
        }

        foreach ($rule['conditions'] as $condition) {
            $questionId = $condition['question_id'] ?? null;
            $expectedAnswer = $condition['answer_id'] ?? null;

            if ($questionId && $expectedAnswer) {
                $userAnswer = $answers[$questionId] ?? null;
                if ($userAnswer != $expectedAnswer) {
                    return false;
                }
            }
        }

        return true;
    }
}

