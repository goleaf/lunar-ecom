<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\ComparisonController;
use Livewire\Component;

class ComparisonIndex extends Component
{
    public function render()
    {
        return app(ComparisonController::class)->index(request());
    }
}


