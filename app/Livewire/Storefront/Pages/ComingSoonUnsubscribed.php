<?php

namespace App\Livewire\Storefront\Pages;

use App\Http\Controllers\Storefront\ComingSoonController;
use Livewire\Component;

class ComingSoonUnsubscribed extends Component
{
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function render()
    {
        return app(ComingSoonController::class)->unsubscribe($this->token);
    }
}


