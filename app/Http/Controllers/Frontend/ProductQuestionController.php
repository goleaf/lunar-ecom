<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductQuestionController extends Controller
{
    public function __construct(
        protected QuestionService $questionService
    ) {}

    /**
     * Display Q&A section for a product.
     */
    public function index(Product $product, Request $request)
    {
        $filters = [
            'answered' => $request->input('answered'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'helpful'),
            'per_page' => $request->input('per_page', 10),
        ];

        $questions = $this->questionService->getProductQuestions($product, $filters);
        $qaCounts = $this->questionService->getQaCount($product);
        $qaCount = $qaCounts['total'] ?? $questions->total();
        $similarQuestions = null;

        return view('frontend.products.qa', compact('product', 'questions', 'qaCount', 'similarQuestions'));
    }

    /**
     * Submit a new question.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|min:10|max:1000',
            'customer_name' => 'required_without:customer_id|string|max:255',
            'email' => 'required_without:customer_id|email|max:255',
            'is_public' => 'boolean',
        ]);

        // Check for similar questions
        $similarQuestions = $this->questionService->findSimilarQuestions($product, $validated['question']);

        if ($similarQuestions->isNotEmpty() && !$request->input('force_submit')) {
            return response()->json([
                'success' => false,
                'similar_questions' => $similarQuestions->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'question' => $q->question,
                        'answer_count' => $q->answers()->count(),
                    ];
                }),
                'message' => __('frontend.messages.similar_questions_found'),
            ], 422);
        }

        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();
        $customerId = $user?->latestCustomer()?->id;

        $question = $this->questionService->submitQuestion($product, array_merge($validated, [
            'customer_id' => $customerId,
        ]));

        return response()->json([
            'success' => true,
            'message' => __('frontend.messages.question_submitted'),
            'question' => $question,
        ]);
    }

    /**
     * Submit an answer (for customers).
     */
    public function answer(Request $request, Product $product, ProductQuestion $question): JsonResponse
    {
        if ($question->product_id !== $product->id) {
            abort(404);
        }

        if (!$question->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.messages.question_not_available'),
            ], 403);
        }

        $validated = $request->validate([
            'answer' => 'required|string|min:10|max:2000',
        ]);

        $answer = $this->questionService->submitAnswer($question, array_merge($validated, [
            'answerer_type' => 'customer',
            'auto_approve' => false, // Customer answers need moderation
        ]));

        return response()->json([
            'success' => true,
            'message' => __('frontend.messages.answer_submitted'),
            'answer' => $answer,
        ]);
    }

    /**
     * Mark question as helpful.
     */
    public function markHelpful(Product $product, ProductQuestion $question): JsonResponse
    {
        if ($question->product_id !== $product->id) {
            abort(404);
        }

        $this->questionService->markHelpful($question);

        return response()->json([
            'success' => true,
            'helpful_count' => $question->fresh()->helpful_count,
        ]);
    }

    /**
     * Mark answer as helpful.
     */
    public function markAnswerHelpful(Product $product, ProductQuestion $question, int $answerId): JsonResponse
    {
        if ($question->product_id !== $product->id) {
            abort(404);
        }

        $answer = $question->answers()->findOrFail($answerId);
        $this->questionService->markHelpful($answer);

        return response()->json([
            'success' => true,
            'helpful_count' => $answer->fresh()->helpful_count,
        ]);
    }

    /**
     * Increment question views.
     */
    public function view(Product $product, ProductQuestion $question): JsonResponse
    {
        if ($question->product_id !== $product->id) {
            abort(404);
        }

        $question->incrementViews();

        return response()->json([
            'success' => true,
            'views_count' => $question->fresh()->views_count,
        ]);
    }

    /**
     * Search questions.
     */
    public function search(Product $product, Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        
        $questions = ProductQuestion::where('product_id', $product->id)
            ->approved()
            ->search($search)
            ->with('answers')
            ->limit(10)
            ->get();

        return response()->json([
            'questions' => $questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question' => $question->question,
                    'answer_count' => $question->answers()->count(),
                    'helpful_count' => $question->helpful_count,
                ];
            }),
        ]);
    }
}




