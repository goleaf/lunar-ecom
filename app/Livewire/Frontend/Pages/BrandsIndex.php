<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\BrandController;
use Livewire\Component;

class BrandsIndex extends Component
{
    public function render()
    {
        return app(BrandController::class)->index(request());
    }
}


