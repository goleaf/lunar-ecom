<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\BundleController;
use Livewire\Component;

class BundlesIndex extends Component
{
    public function render()
    {
        return app(BundleController::class)->index(request());
    }
}


