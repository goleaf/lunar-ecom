<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\SearchController;
use Livewire\Component;

class SearchIndex extends Component
{
    public function render()
    {
        return app(SearchController::class)->index(request());
    }
}


