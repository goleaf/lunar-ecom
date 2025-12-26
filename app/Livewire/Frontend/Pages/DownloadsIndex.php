<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\DownloadController;
use Livewire\Component;

class DownloadsIndex extends Component
{
    public function render()
    {
        return app(DownloadController::class)->index(request());
    }
}


