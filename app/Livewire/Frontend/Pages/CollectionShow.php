<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\CollectionController;
use Livewire\Component;

class CollectionShow extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        return app(CollectionController::class)->show($this->slug, request());
    }
}


