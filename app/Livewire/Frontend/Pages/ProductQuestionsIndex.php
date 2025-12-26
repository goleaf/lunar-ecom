<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\ProductQuestionController;
use App\Models\Product;
use Livewire\Component;

class ProductQuestionsIndex extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function render()
    {
        return app(ProductQuestionController::class)->index($this->product, request());
    }
}


