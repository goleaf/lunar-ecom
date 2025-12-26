<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\CollectionController;
use Livewire\Component;

class CollectionsIndex extends Component
{
    public function render()
    {
        return app(CollectionController::class)->index();
    }
}


