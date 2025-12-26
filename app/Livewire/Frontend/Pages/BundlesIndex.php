<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\BundleController;
use Livewire\Component;

class BundlesIndex extends Component
{
    public function render()
    {
        return app(BundleController::class)->index(request());
    }
}


