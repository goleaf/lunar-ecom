<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\BrandController;
use Livewire\Component;

class BrandsIndex extends Component
{
    public function render()
    {
        return app(BrandController::class)->index(request());
    }
}


