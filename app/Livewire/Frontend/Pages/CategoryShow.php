<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\CategoryController;
use Livewire\Component;

class CategoryShow extends Component
{
    public string $path;

    public function mount(string $path): void
    {
        $this->path = $path;
    }

    public function render()
    {
        return app(CategoryController::class)->show($this->path, request());
    }
}


