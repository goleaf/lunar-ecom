<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\ComparisonController;
use Livewire\Component;

class ComparisonIndex extends Component
{
    public function render()
    {
        return app(ComparisonController::class)->index(request());
    }
}


