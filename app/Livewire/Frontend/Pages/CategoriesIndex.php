<?php

namespace App\Livewire\Frontend\Pages;

use App\Repositories\CategoryRepository;
use Livewire\Component;

class CategoriesIndex extends Component
{
    public function render()
    {
        $categories = app(CategoryRepository::class)->getRootCategories();

        return view('frontend.categories.index', [
            'categories' => $categories,
        ]);
    }
}


