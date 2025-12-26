<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\BrandController;
use Livewire\Component;

class BrandShow extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        return app(BrandController::class)->show($this->slug, request());
    }
}


