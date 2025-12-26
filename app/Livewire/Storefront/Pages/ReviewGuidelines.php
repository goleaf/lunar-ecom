<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\ReviewController;
use Livewire\Component;

class ReviewGuidelines extends Component
{
    public function render()
    {
        return app(ReviewController::class)->guidelines(request());
    }
}


