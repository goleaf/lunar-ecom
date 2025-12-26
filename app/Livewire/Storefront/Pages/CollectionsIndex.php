<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\CollectionController;
use Livewire\Component;

class CollectionsIndex extends Component
{
    public function render()
    {
        return app(CollectionController::class)->index();
    }
}


