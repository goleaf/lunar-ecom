<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\SearchController;
use Livewire\Component;

class SearchIndex extends Component
{
    public function render()
    {
        return app(SearchController::class)->index(request());
    }
}


