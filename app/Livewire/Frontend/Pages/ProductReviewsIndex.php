<?php

namespace App\Livewire\Frontend\Pages;

use App\Models\Product;
use Livewire\Component;

class ProductReviewsIndex extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function render()
    {
        $validated = request()->validate([
            'filter' => 'in:most_helpful,most_recent,highest_rating,lowest_rating,verified_only',
            'rating' => 'integer|min:1|max:5',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
        ]);

        $query = $this->product->approvedReviews()
            ->with(['customer', 'helpfulVotes', 'media', 'responder']);

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

        if (isset($validated['rating'])) {
            $query->where('rating', $validated['rating']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $reviews = $query->paginate($perPage);
        $product = $this->product;

        return view('frontend.reviews.index', compact('product', 'reviews'));
    }
}


