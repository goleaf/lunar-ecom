<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\HomepageController;
use Livewire\Component;

class Homepage extends Component
{
    public function render()
    {
        return app(HomepageController::class)->index(request());
    }
}


