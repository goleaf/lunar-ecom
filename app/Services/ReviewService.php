<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing product reviews and ratings.
 */
class ReviewService
{
    /**
     * Create a new review.
     *
     * @param  array  $data
     * @return Review
     * @throws ValidationException
     */
    public function createReview(array $data): Review
    {
        // Validate content length
        if (strlen($data['content']) < 10) {
            throw ValidationException::withMessages([
                'content' => ['Review content must be at least 10 characters.']
            ]);
        }

        if (strlen($data['content']) > 5000) {
            throw ValidationException::withMessages([
                'content' => ['Review content must not exceed 5000 characters.']
            ]);
        }

        // Validate title length
        if (strlen($data['title']) < 10 || strlen($data['title']) > 255) {
            throw ValidationException::withMessages([
                'title' => ['Review title must be between 10 and 255 characters.']
            ]);
        }

        // Check if customer already reviewed this product
        if (isset($data['customer_id'])) {
            $existingReview = Review::where('product_id', $data['product_id'])
                ->where('customer_id', $data['customer_id'])
                ->first();

            if ($existingReview) {
                throw ValidationException::withMessages([
                    'product_id' => ['You have already reviewed this product.']
                ]);
            }
        }

        // Verify purchase if order_id provided
        $isVerifiedPurchase = false;
        if (isset($data['order_id']) && isset($data['customer_id'])) {
            $isVerifiedPurchase = $this->verifyPurchase(
                $data['product_id'],
                $data['order_id'],
                $data['customer_id']
            );
        }

        // Create review
        $review = Review::create([
            'product_id' => $data['product_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'rating' => $data['rating'],
            'title' => $data['title'],
            'content' => $data['content'],
            'pros' => $data['pros'] ?? [],
            'cons' => $data['cons'] ?? [],
            'recommended' => $data['recommended'] ?? ($data['rating'] >= 4),
            'is_verified_purchase' => $isVerifiedPurchase,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'is_approved' => false, // Requires moderation
        ]);

        // Attach media if provided
        if (isset($data['images']) && is_array($data['images'])) {
            $this->attachMedia($review, $data['images']);
        }

        return $review->fresh();
    }

    /**
     * Approve a review.
     *
     * @param  Review  $review
     * @param  int|null  $approvedBy
     * @return Review
     */
    public function approveReview(Review $review, ?int $approvedBy = null): Review
    {
        $review->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy ?? auth()->id(),
        ]);

        // Update product rating cache
        $review->product->updateRatingCache();

        return $review->fresh();
    }

    /**
     * Reject a review.
     *
     * @param  Review  $review
     * @param  string|null  $reason
     * @return Review
     */
    public function rejectReview(Review $review, ?string $reason = null): Review
    {
        $review->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        // If was previously approved, update cache
        if ($review->wasChanged('is_approved')) {
            $review->product->updateRatingCache();
        }

        return $review->fresh();
    }

    /**
     * Report a review.
     *
     * @param  Review  $review
     * @param  string|null  $reason
     * @return Review
     */
    public function reportReview(Review $review, ?string $reason = null): Review
    {
        $review->increment('report_count');
        $review->update(['is_reported' => true]);

        return $review->fresh();
    }

    /**
     * Mark review as helpful or not helpful.
     *
     * @param  Review  $review
     * @param  bool  $isHelpful
     * @param  int|null  $customerId
     * @return ReviewHelpfulVote
     * @throws ValidationException
     */
    public function markHelpful(Review $review, bool $isHelpful, ?int $customerId = null): ReviewHelpfulVote
    {
        $sessionId = Session::getId();
        $ipAddress = request()->ip();

        // Check if already voted (spam prevention)
        if ($customerId && $review->hasVoted($customerId)) {
            throw ValidationException::withMessages([
                'review_id' => ['You have already voted on this review.']
            ]);
        }

        if (!$customerId && $review->hasVoted(null, $sessionId)) {
            throw ValidationException::withMessages([
                'review_id' => ['You have already voted on this review.']
            ]);
        }

        if (!$customerId && !$sessionId && $review->hasVoted(null, null, $ipAddress)) {
            throw ValidationException::withMessages([
                'review_id' => ['You have already voted on this review.']
            ]);
        }

        // Create or update vote
        $vote = ReviewHelpfulVote::updateOrCreate(
            [
                'review_id' => $review->id,
                'customer_id' => $customerId,
                'session_id' => $customerId ? null : $sessionId,
                'ip_address' => $customerId ? null : $ipAddress,
            ],
            [
                'is_helpful' => $isHelpful,
            ]
        );

        // Update review helpful counts
        $review->updateHelpfulCounts();

        return $vote;
    }

    /**
     * Bulk approve reviews.
     *
     * @param  array  $reviewIds
     * @return int  Number of approved reviews
     */
    public function bulkApprove(array $reviewIds): int
    {
        $approved = 0;
        $approvedBy = auth()->id();

        DB::transaction(function () use ($reviewIds, $approvedBy, &$approved) {
            $reviews = Review::whereIn('id', $reviewIds)
                ->where('is_approved', false)
                ->get();

            foreach ($reviews as $review) {
                $this->approveReview($review, $approvedBy);
                $approved++;
            }
        });

        return $approved;
    }

    /**
     * Bulk reject reviews.
     *
     * @param  array  $reviewIds
     * @return int  Number of rejected reviews
     */
    public function bulkReject(array $reviewIds): int
    {
        $rejected = 0;

        DB::transaction(function () use ($reviewIds, &$rejected) {
            $reviews = Review::whereIn('id', $reviewIds)
                ->where('is_approved', true)
                ->get();

            foreach ($reviews as $review) {
                $this->rejectReview($review);
                $rejected++;
            }
        });

        return $rejected;
    }

    /**
     * Add admin response to review.
     *
     * @param  Review  $review
     * @param  string  $response
     * @param  int|null  $respondedBy
     * @return Review
     */
    public function addAdminResponse(Review $review, string $response, ?int $respondedBy = null): Review
    {
        $review->update([
            'admin_response' => $response,
            'responded_at' => now(),
            'responded_by' => $respondedBy ?? auth()->id(),
        ]);

        return $review->fresh();
    }

    /**
     * Verify if purchase is valid for verified purchase badge.
     *
     * @param  int  $productId
     * @param  int  $orderId
     * @param  int  $customerId
     * @return bool
     */
    protected function verifyPurchase(int $productId, int $orderId, int $customerId): bool
    {
        $order = \Lunar\Models\Order::where('id', $orderId)
            ->where('customer_id', $customerId)
            ->whereNotNull('placed_at')
            ->where('placed_at', '<=', now())
            ->first();

        if (!$order) {
            return false;
        }

        // Check if order contains this product (via order lines -> purchasable -> product)
        // Purchasable is polymorphic and can be ProductVariant
        return $order->lines()
            ->where('purchasable_type', \Lunar\Models\ProductVariant::class)
            ->whereHas('purchasable', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();
    }

    /**
     * Attach media to review (max 5 images).
     *
     * @param  Review  $review
     * @param  array  $images
     * @return void
     * @throws ValidationException
     */
    protected function attachMedia(Review $review, array $images): void
    {
        if (count($images) > 5) {
            throw ValidationException::withMessages([
                'images' => ['Maximum 5 images allowed per review.']
            ]);
        }

        foreach ($images as $image) {
            if ($image instanceof \Illuminate\Http\UploadedFile) {
                $review->addMedia($image)
                    ->toMediaCollection('images');
            } elseif (is_string($image)) {
                // Handle base64 or URL
                if (filter_var($image, FILTER_VALIDATE_URL)) {
                    $review->addMediaFromUrl($image)
                        ->toMediaCollection('images');
                } else {
                    // Handle base64
                    $review->addMediaFromBase64($image)
                        ->toMediaCollection('images');
                }
            }
        }
    }

    /**
     * Get review guidelines.
     *
     * @return array
     */
    public function getReviewGuidelines(): array
    {
        return [
            'title_min' => 10,
            'title_max' => 255,
            'content_min' => 10,
            'content_max' => 5000,
            'max_images' => 5,
            'rating_range' => [1, 2, 3, 4, 5],
            'guidelines' => [
                'Be honest and fair in your review',
                'Focus on the product, not the seller',
                'Include specific details about your experience',
                'Keep language respectful and appropriate',
                'Do not include personal information',
            ],
        ];
    }
}

