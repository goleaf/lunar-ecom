<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductAnswer;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductQuestionController extends Controller
{
    public function __construct(
        protected QuestionService $questionService
    ) {}

    /**
     * Display moderation queue.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        
        $questions = ProductQuestion::with(['product', 'customer'])
            ->where('status', $status)
            ->orderBy('asked_at', 'desc')
            ->paginate(20);

        $stats = [
            'pending' => ProductQuestion::where('status', 'pending')->count(),
            'approved' => ProductQuestion::where('status', 'approved')->count(),
            'rejected' => ProductQuestion::where('status', 'rejected')->count(),
            'unanswered' => ProductQuestion::where('is_answered', false)->approved()->count(),
        ];

        return view('admin.products.questions.index', compact('questions', 'stats', 'status'));
    }

    /**
     * Show a question.
     */
    public function show(ProductQuestion $question)
    {
        $question->load(['product', 'customer', 'answers.answerer', 'moderator']);
        
        return view('admin.products.questions.show', compact('question'));
    }

    /**
     * Moderate a question.
     */
    public function moderate(Request $request, ProductQuestion $question): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,spam',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->questionService->moderateQuestion(
            $question,
            $validated['status'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Question moderated successfully.',
            'question' => $question->fresh(),
        ]);
    }

    /**
     * Submit an answer (admin).
     */
    public function answer(Request $request, ProductQuestion $question): JsonResponse
    {
        $validated = $request->validate([
            'answer' => 'required|string|min:10|max:2000',
            'is_official' => 'boolean',
        ]);

        $answer = $this->questionService->submitAnswer($question, array_merge($validated, [
            'answerer_type' => 'admin',
            'is_official' => $validated['is_official'] ?? true,
            'auto_approve' => true,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Answer submitted successfully.',
            'answer' => $answer,
        ]);
    }

    /**
     * Moderate an answer.
     */
    public function moderateAnswer(Request $request, ProductQuestion $question, ProductAnswer $answer): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->questionService->moderateAnswer(
            $answer,
            $validated['status'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Answer moderated successfully.',
            'answer' => $answer->fresh(),
        ]);
    }

    /**
     * Bulk moderate questions.
     */
    public function bulkModerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:lunar_product_questions,id',
            'status' => 'required|in:approved,rejected,spam',
            'notes' => 'nullable|string|max:1000',
        ]);

        $count = 0;
        foreach ($validated['question_ids'] as $questionId) {
            $question = ProductQuestion::find($questionId);
            if ($question) {
                $this->questionService->moderateQuestion(
                    $question,
                    $validated['status'],
                    $validated['notes'] ?? null
                );
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} questions moderated successfully.",
        ]);
    }

    /**
     * Get Q&A metrics for a product.
     */
    public function metrics(Product $product): JsonResponse
    {
        $metrics = \App\Models\ProductQaMetric::where('product_id', $product->id)
            ->orderBy('period_end', 'desc')
            ->first();

        $qaCount = $this->questionService->getQaCount($product);

        return response()->json([
            'metrics' => $metrics,
            'count' => $qaCount,
        ]);
    }
}

