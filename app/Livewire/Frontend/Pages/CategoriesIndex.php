<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\CategoryController;
use Livewire\Component;

class CategoriesIndex extends Component
{
    public function render()
    {
        return app(CategoryController::class)->index(request());
    }
}


