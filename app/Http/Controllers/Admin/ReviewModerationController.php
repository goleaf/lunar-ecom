<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for review moderation.
 */
class ReviewModerationController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Display moderation queue.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function index(Request $request)
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        // Prefer Filament for the admin UI. Keep JSON support for internal tooling.
        if (! $request->wantsJson()) {
            return redirect()->route('filament.admin.resources.reviews.index', $request->query());
        }

        $query = Review::with(['product', 'customer', 'order', 'media'])
            ->orderByDesc('created_at');

        // Filter by status
        $status = $request->get('status', 'pending');
        switch ($status) {
            case 'pending':
                $query->pending();
                break;
            case 'approved':
                $query->approved();
                break;
            case 'reported':
                $query->reported();
                break;
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->get('rating'));
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        $perPage = $request->get('per_page', 20);
        $reviews = $query->paginate($perPage);

        return response()->json($reviews);
    }

    /**
     * Show a single review for moderation.
     *
     * @param  Review  $review
     * @return JsonResponse
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['product', 'customer', 'order', 'media', 'helpfulVotes']);

        return response()->json($review);
    }

    /**
     * Approve a review.
     *
     * @param  Request  $request
     * @param  Review  $review
     * @return JsonResponse
     */
    public function approve(Request $request, Review $review): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        // NOTE: `approved_by` references the `users` table, not `staff`. If no web user is present, this stays null.
        $this->reviewService->approveReview($review, auth('web')->id());

        return response()->json([
            'message' => 'Review approved successfully',
            'review' => $review->fresh(),
        ]);
    }

    /**
     * Reject a review.
     *
     * @param  Request  $request
     * @param  Review  $review
     * @return JsonResponse
     */
    public function reject(Request $request, Review $review): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->reviewService->rejectReview($review, $validated['reason'] ?? null);

        return response()->json([
            'message' => 'Review rejected successfully',
            'review' => $review->fresh(),
        ]);
    }

    /**
     * Bulk approve reviews.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'integer|exists:reviews,id',
        ]);

        $count = $this->reviewService->bulkApprove($validated['review_ids']);

        return response()->json([
            'message' => "{$count} review(s) approved successfully",
            'count' => $count,
        ]);
    }

    /**
     * Bulk reject reviews.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function bulkReject(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'integer|exists:reviews,id',
        ]);

        $count = $this->reviewService->bulkReject($validated['review_ids']);

        return response()->json([
            'message' => "{$count} review(s) rejected successfully",
            'count' => $count,
        ]);
    }

    /**
     * Add admin response to review.
     *
     * @param  Request  $request
     * @param  Review  $review
     * @return JsonResponse
     */
    public function addResponse(Request $request, Review $review): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'response' => 'required|string|min:10|max:2000',
        ]);

        // NOTE: `responded_by` references the `users` table, not `staff`. If no web user is present, this stays null.
        $this->reviewService->addAdminResponse($review, $validated['response'], auth('web')->id());

        return response()->json([
            'message' => 'Admin response added successfully',
            'review' => $review->fresh(),
        ]);
    }

    /**
     * Get moderation statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'pending' => Review::pending()->count(),
            'approved' => Review::approved()->count(),
            'reported' => Review::reported()->count(),
            'total' => Review::count(),
        ];

        return response()->json($stats);
    }
}
