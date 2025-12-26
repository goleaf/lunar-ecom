<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\BundleController;
use App\Models\Bundle;
use Livewire\Component;

class BundleShow extends Component
{
    public Bundle $bundle;

    public function mount(Bundle $bundle): void
    {
        $this->bundle = $bundle;
    }

    public function render()
    {
        return app(BundleController::class)->show(request(), $this->bundle);
    }
}


