<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\CategoryController;
use Livewire\Component;

class CategoriesIndex extends Component
{
    public function render()
    {
        return app(CategoryController::class)->index(request());
    }
}


