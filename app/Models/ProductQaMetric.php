<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

class ProductQaMetric extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_qa_metrics';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'total_questions',
        'approved_questions',
        'pending_questions',
        'answered_questions',
        'unanswered_questions',
        'total_answers',
        'official_answers',
        'customer_answers',
        'average_response_time_hours',
        'answer_rate',
        'satisfaction_score',
        'total_views',
        'total_helpful_votes',
        'period_start',
        'period_end',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_questions' => 'integer',
        'approved_questions' => 'integer',
        'pending_questions' => 'integer',
        'answered_questions' => 'integer',
        'unanswered_questions' => 'integer',
        'total_answers' => 'integer',
        'official_answers' => 'integer',
        'customer_answers' => 'integer',
        'average_response_time_hours' => 'decimal:2',
        'answer_rate' => 'decimal:2',
        'satisfaction_score' => 'decimal:2',
        'total_views' => 'integer',
        'total_helpful_votes' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
