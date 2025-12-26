<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\ReviewController;
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
        return app(ReviewController::class)->index(request(), $this->product);
    }
}


