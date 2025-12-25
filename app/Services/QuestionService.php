<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductAnswer;
use App\Models\ProductQaMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\QuestionAnsweredNotification;
use App\Notifications\NewQuestionNotification;
use Carbon\Carbon;

class QuestionService
{
    /**
     * Submit a new question.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return ProductQuestion
     */
    public function submitQuestion(Product $product, array $data): ProductQuestion
    {
        return DB::transaction(function () use ($product, $data) {
            // Check for duplicate questions
            $similarQuestions = $this->findSimilarQuestions($product, $data['question'] ?? '');
            
            // Create the question
            $question = ProductQuestion::create([
                'product_id' => $product->id,
                'customer_id' => $data['customer_id'] ?? auth()->user()?->customer?->id,
                'customer_name' => $data['customer_name'] ?? auth()->user()?->name ?? 'Guest',
                'email' => $data['email'] ?? auth()->user()?->email,
                'question' => $data['question'],
                'status' => $data['auto_approve'] ?? false ? 'approved' : 'pending',
                'is_public' => $data['is_public'] ?? true,
            ]);

            // Notify admins of new question
            $this->notifyAdmins($question);

            return $question;
        });
    }

    /**
     * Submit an answer to a question.
     *
     * @param  ProductQuestion  $question
     * @param  array  $data
     * @return ProductAnswer
     */
    public function submitAnswer(ProductQuestion $question, array $data): ProductAnswer
    {
        return DB::transaction(function () use ($question, $data) {
            $answerer = auth()->user();
            $answererType = $data['answerer_type'] ?? ($answerer ? 'admin' : 'customer');
            $answererId = $data['answerer_id'] ?? $answerer?->id;

            $answer = ProductAnswer::create([
                'question_id' => $question->id,
                'answerer_type' => $answererType,
                'answerer_id' => $answererId,
                'answer' => $data['answer'],
                'is_official' => $data['is_official'] ?? ($answererType === 'admin' || $answererType === 'store'),
                'is_approved' => $data['auto_approve'] ?? ($answererType === 'admin' || $answererType === 'store'),
                'status' => $data['auto_approve'] ?? ($answererType === 'admin' || $answererType === 'store') ? 'approved' : 'pending',
            ]);

            // Mark question as answered
            $question->update(['is_answered' => true]);

            // Notify customer
            $this->notifyCustomer($question, $answer);

            // Update metrics
            $this->updateMetrics($question->product);

            return $answer;
        });
    }

    /**
     * Mark a question or answer as helpful.
     *
     * @param  ProductQuestion|ProductAnswer  $item
     * @return bool
     */
    public function markHelpful($item): bool
    {
        if ($item instanceof ProductQuestion) {
            $item->markHelpful();
            return true;
        } elseif ($item instanceof ProductAnswer) {
            $item->markHelpful();
            return true;
        }

        return false;
    }

    /**
     * Mark a question or answer as not helpful.
     *
     * @param  ProductQuestion|ProductAnswer  $item
     * @return bool
     */
    public function markNotHelpful($item): bool
    {
        if ($item instanceof ProductQuestion) {
            $item->markNotHelpful();
            return true;
        } elseif ($item instanceof ProductAnswer) {
            $item->markNotHelpful();
            return true;
        }

        return false;
    }

    /**
     * Moderate a question.
     *
     * @param  ProductQuestion  $question
     * @param  string  $status
     * @param  string|null  $notes
     * @return ProductQuestion
     */
    public function moderateQuestion(ProductQuestion $question, string $status, ?string $notes = null): ProductQuestion
    {
        $question->update([
            'status' => $status,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
            'moderation_notes' => $notes,
        ]);

        // Update metrics
        $this->updateMetrics($question->product);

        return $question;
    }

    /**
     * Moderate an answer.
     *
     * @param  ProductAnswer  $answer
     * @param  string  $status
     * @param  string|null  $notes
     * @return ProductAnswer
     */
    public function moderateAnswer(ProductAnswer $answer, string $status, ?string $notes = null): ProductAnswer
    {
        $answer->update([
            'status' => $status,
            'is_approved' => $status === 'approved',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
            'moderation_notes' => $notes,
        ]);

        return $answer;
    }

    /**
     * Find similar questions to prevent duplicates.
     *
     * @param  Product  $product
     * @param  string  $question
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findSimilarQuestions(Product $product, string $question): \Illuminate\Database\Eloquent\Collection
    {
        // Use full-text search and similarity matching
        $similar = ProductQuestion::where('product_id', $product->id)
            ->approved()
            ->where(function ($query) use ($question) {
                $query->whereFullText('question', $question)
                    ->orWhere('question', 'like', '%' . substr($question, 0, 20) . '%');
            })
            ->limit(5)
            ->get();

        return $similar;
    }

    /**
     * Get questions for a product.
     *
     * @param  Product  $product
     * @param  array  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProductQuestions(Product $product, array $filters = [])
    {
        $query = ProductQuestion::where('product_id', $product->id)
            ->approved()
            ->with(['answers', 'customer']);

        // Filter by answered/unanswered
        if (isset($filters['answered'])) {
            if ($filters['answered']) {
                $query->answered();
            } else {
                $query->unanswered();
            }
        }

        // Search
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sort
        $sortBy = $filters['sort'] ?? 'helpful';
        switch ($sortBy) {
            case 'helpful':
                $query->orderBy('helpful_count', 'desc');
                break;
            case 'recent':
                $query->orderBy('asked_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('asked_at', 'asc');
                break;
            default:
                $query->orderBy('helpful_count', 'desc');
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Get Q&A count for a product.
     *
     * @param  Product  $product
     * @return array
     */
    public function getQaCount(Product $product): array
    {
        return [
            'total' => ProductQuestion::where('product_id', $product->id)->approved()->count(),
            'answered' => ProductQuestion::where('product_id', $product->id)->approved()->answered()->count(),
            'unanswered' => ProductQuestion::where('product_id', $product->id)->approved()->unanswered()->count(),
        ];
    }

    /**
     * Update Q&A metrics for a product.
     *
     * @param  Product  $product
     * @return void
     */
    public function updateMetrics(Product $product): void
    {
        $today = now()->toDateString();
        
        $questions = ProductQuestion::where('product_id', $product->id)->get();
        $answers = ProductAnswer::whereIn('question_id', $questions->pluck('id'))->get();

        // Calculate metrics
        $totalQuestions = $questions->count();
        $approvedQuestions = $questions->where('status', 'approved')->count();
        $pendingQuestions = $questions->where('status', 'pending')->count();
        $answeredQuestions = $questions->where('is_answered', true)->count();
        $unansweredQuestions = $questions->where('is_answered', false)->count();

        $totalAnswers = $answers->count();
        $officialAnswers = $answers->where('is_official', true)->count();
        $customerAnswers = $answers->where('answerer_type', 'customer')->count();

        // Calculate average response time
        $answeredQuestionsWithTime = $questions->where('is_answered', true)
            ->filter(function ($question) {
                $firstAnswer = $question->answers()->orderBy('answered_at')->first();
                return $firstAnswer && $question->asked_at;
            });

        $totalResponseTime = $answeredQuestionsWithTime->sum(function ($question) {
            $firstAnswer = $question->answers()->orderBy('answered_at')->first();
            return $question->asked_at->diffInHours($firstAnswer->answered_at);
        });

        $averageResponseTime = $answeredQuestionsWithTime->count() > 0
            ? $totalResponseTime / $answeredQuestionsWithTime->count()
            : null;

        // Calculate answer rate
        $answerRate = $totalQuestions > 0
            ? ($answeredQuestions / $totalQuestions) * 100
            : 0;

        // Calculate satisfaction score (based on helpful votes)
        $totalHelpful = $questions->sum('helpful_count') + $answers->sum('helpful_count');
        $totalNotHelpful = $questions->sum('not_helpful_count') + $answers->sum('not_helpful_count');
        $totalVotes = $totalHelpful + $totalNotHelpful;
        
        $satisfactionScore = $totalVotes > 0
            ? ($totalHelpful / $totalVotes) * 5 // Scale to 5
            : null;

        // Total views and helpful votes
        $totalViews = $questions->sum('views_count');
        $totalHelpfulVotes = $totalHelpful;

        // Update or create metric
        ProductQaMetric::updateOrCreate(
            [
                'product_id' => $product->id,
                'period_start' => $today,
                'period_end' => $today,
            ],
            [
                'total_questions' => $totalQuestions,
                'approved_questions' => $approvedQuestions,
                'pending_questions' => $pendingQuestions,
                'answered_questions' => $answeredQuestions,
                'unanswered_questions' => $unansweredQuestions,
                'total_answers' => $totalAnswers,
                'official_answers' => $officialAnswers,
                'customer_answers' => $customerAnswers,
                'average_response_time_hours' => $averageResponseTime,
                'answer_rate' => round($answerRate, 2),
                'satisfaction_score' => $satisfactionScore ? round($satisfactionScore, 2) : null,
                'total_views' => $totalViews,
                'total_helpful_votes' => $totalHelpfulVotes,
            ]
        );
    }

    /**
     * Notify admins of new question.
     *
     * @param  ProductQuestion  $question
     * @return void
     */
    protected function notifyAdmins(ProductQuestion $question): void
    {
        try {
            // Get admin users
            $userClass = class_exists(\Lunar\Models\User::class) 
                ? \Lunar\Models\User::class 
                : \App\Models\User::class;
            
            $admins = $userClass::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewQuestionNotification($question));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of new question: ' . $e->getMessage());
        }
    }

    /**
     * Notify customer when question is answered.
     *
     * @param  ProductQuestion  $question
     * @param  ProductAnswer  $answer
     * @return void
     */
    protected function notifyCustomer(ProductQuestion $question, ProductAnswer $answer): void
    {
        try {
            if ($question->email) {
                Notification::route('mail', $question->email)
                    ->notify(new QuestionAnsweredNotification($question, $answer));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify customer of answer: ' . $e->getMessage());
        }
    }

    /**
     * Generate structured data for SEO.
     *
     * @param  Product  $product
     * @return array
     */
    public function generateStructuredData(Product $product): array
    {
        $questions = ProductQuestion::where('product_id', $product->id)
            ->approved()
            ->with('answers')
            ->get();

        $qaPage = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [],
        ];

        foreach ($questions as $question) {
            $answer = $question->answers()->first();
            
            if (!$answer) {
                continue;
            }

            $qaPage['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $question->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer->answer,
                ],
            ];
        }

        return $qaPage;
    }
}

