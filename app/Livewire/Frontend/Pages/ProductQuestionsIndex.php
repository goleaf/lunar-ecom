<?php

namespace App\Livewire\Frontend\Pages;

use App\Models\Product;
use App\Services\QuestionService;
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
        $request = request();

        $filters = [
            'answered' => $request->input('answered'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'helpful'),
            'per_page' => $request->input('per_page', 10),
        ];

        $questionService = app(QuestionService::class);
        $questions = $questionService->getProductQuestions($this->product, $filters);
        $qaCounts = $questionService->getQaCount($this->product);

        $qaCount = $qaCounts['total'] ?? $questions->total();
        $similarQuestions = null;
        $product = $this->product;

        return view('frontend.products.qa', compact('product', 'questions', 'qaCount', 'similarQuestions'));
    }
}


