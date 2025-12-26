<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\ReviewController;
use Livewire\Component;

class ReviewGuidelines extends Component
{
    public function render()
    {
        return app(ReviewController::class)->guidelines(request());
    }
}


