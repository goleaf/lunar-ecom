<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\DownloadController;
use Livewire\Component;

class DownloadsIndex extends Component
{
    public function render()
    {
        return app(DownloadController::class)->index(request());
    }
}


