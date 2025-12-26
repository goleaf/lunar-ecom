<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Lunar\Facades\StorefrontSession;

/**
 * Controller for storefront review functionality.
 */
class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Get reviews for a product with filtering.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request, Product $product)
    {
        $validated = $request->validate([
            'filter' => 'in:most_helpful,most_recent,highest_rating,lowest_rating,verified_only',
            'rating' => 'integer|min:1|max:5',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
        ]);

        $query = $product->approvedReviews()
            ->with(['customer', 'helpfulVotes', 'media', 'responder']);

        // Apply filters
        $filter = $validated['filter'] ?? 'most_helpful';
        switch ($filter) {
            case 'most_helpful':
                $query->mostHelpful();
                break;
            case 'most_recent':
                $query->mostRecent();
                break;
            case 'highest_rating':
                $query->highestRating();
                break;
            case 'lowest_rating':
                $query->lowestRating();
                break;
            case 'verified_only':
                $query->verifiedPurchase();
                break;
        }

        // Filter by rating
        if (isset($validated['rating'])) {
            $query->where('rating', $validated['rating']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $reviews = $query->paginate($perPage);

        if ($request->wantsJson()) {
            // Get aggregate ratings
            $aggregateRatings = [
                'average_rating' => $product->average_rating ?? 0,
                'total_reviews' => $product->total_reviews ?? 0,
                'rating_distribution' => $product->getRatingDistribution(),
            ];

            return response()->json([
                'reviews' => $reviews,
                'aggregate_ratings' => $aggregateRatings,
            ]);
        }

        return view('frontend.reviews.index', compact('product', 'reviews'));
    }

    /**
     * Store a new review.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|min:10|max:255',
            'content' => 'required|string|min:10|max:5000',
            'pros' => 'nullable|array',
            'pros.*' => 'string|max:255',
            'cons' => 'nullable|array',
            'cons.*' => 'string|max:255',
            'recommended' => 'boolean',
            'order_id' => 'nullable|exists:lunar_orders,id',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,webp|max:2048',
        ]);

        try {
            // Get customer ID from authenticated user
            $user = auth()->user();
            $customer = $user?->latestCustomer() ?? \Lunar\Facades\StorefrontSession::getCustomer();
            $customerId = $customer?->id ?? null;

            $review = $this->reviewService->createReview([
                'product_id' => $product->id,
                'customer_id' => $customerId,
                'order_id' => $validated['order_id'] ?? null,
                'rating' => $validated['rating'],
                'title' => $validated['title'],
                'content' => $validated['content'],
                'pros' => array_filter($validated['pros'] ?? []),
                'cons' => array_filter($validated['cons'] ?? []),
                'recommended' => $validated['recommended'] ?? ($validated['rating'] >= 4),
                'images' => $request->file('images') ?? [],
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Review submitted successfully. It will be reviewed before publication.',
                    'review' => $review,
                ], 201);
            }

            return redirect()
                ->route('frontend.products.show', $product->urls->first()?->slug ?? $product->id)
                ->with('success', 'Review submitted successfully. It will be reviewed before publication.');
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Mark review as helpful or not helpful.
     *
     * @param  Request  $request
     * @param  Review  $review
     * @return JsonResponse
     */
    public function markHelpful(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'is_helpful' => 'required|boolean',
        ]);

        try {
            $user = auth()->user();
            $customer = $user?->latestCustomer() ?? StorefrontSession::getCustomer();
            $customerId = $customer?->id ?? null;

            $vote = $this->reviewService->markHelpful(
                $review,
                $validated['is_helpful'],
                $customerId
            );

            return response()->json([
                'message' => 'Vote recorded successfully',
                'vote' => $vote,
                'review' => $review->fresh(['helpfulVotes']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'You have already voted on this review',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Report a review.
     *
     * @param  Request  $request
     * @param  Review  $review
     * @return JsonResponse
     */
    public function report(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->reviewService->reportReview($review, $validated['reason'] ?? null);

        return response()->json([
            'message' => 'Review reported successfully. Thank you for your feedback.',
        ]);
    }

    /**
     * Get review guidelines.
     *
     * @return \Illuminate\View\View|JsonResponse
     */
    public function guidelines(Request $request)
    {
        $guidelines = $this->reviewService->getReviewGuidelines();

        if ($request->wantsJson()) {
            return response()->json($guidelines);
        }

        return view('frontend.reviews.guidelines', compact('guidelines'));
    }
}


