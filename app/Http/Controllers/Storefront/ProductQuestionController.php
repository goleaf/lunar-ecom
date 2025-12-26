<?php

namespace App\Http\Controllers\Storefront;

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
        $qaCount = $this->questionService->getQaCount($product);
        $similarQuestions = null;

        return view('storefront.products.qa', compact('product', 'questions', 'qaCount', 'similarQuestions'));
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
                'message' => 'Similar questions found. Please review them before submitting.',
            ], 422);
        }

        $question = $this->questionService->submitQuestion($product, array_merge($validated, [
            'customer_id' => auth()->user()?->customer?->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Your question has been submitted and will be reviewed.',
            'question' => $question,
        ]);
    }

    /**
     * Submit an answer (for customers).
     */
    public function answer(Request $request, ProductQuestion $question): JsonResponse
    {
        if (!$question->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'This question is not available for answering.',
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
            'message' => 'Your answer has been submitted and will be reviewed.',
            'answer' => $answer,
        ]);
    }

    /**
     * Mark question as helpful.
     */
    public function markHelpful(ProductQuestion $question): JsonResponse
    {
        $this->questionService->markHelpful($question);

        return response()->json([
            'success' => true,
            'helpful_count' => $question->fresh()->helpful_count,
        ]);
    }

    /**
     * Mark answer as helpful.
     */
    public function markAnswerHelpful(ProductQuestion $question, int $answerId): JsonResponse
    {
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
    public function view(ProductQuestion $question): JsonResponse
    {
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


