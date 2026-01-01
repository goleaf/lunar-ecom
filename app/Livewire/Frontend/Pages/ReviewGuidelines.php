<?php

namespace App\Livewire\Frontend\Pages;

use App\Services\ReviewService;
use Livewire\Component;

class ReviewGuidelines extends Component
{
    public function render()
    {
        $guidelines = app(ReviewService::class)->getReviewGuidelines();

        return view('frontend.reviews.guidelines', compact('guidelines'));
    }
}


