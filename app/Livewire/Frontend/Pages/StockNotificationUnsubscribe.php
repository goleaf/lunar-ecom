<?php

namespace App\Livewire\Frontend\Pages;

use App\Http\Controllers\Frontend\StockNotificationController;
use Livewire\Component;

class StockNotificationUnsubscribe extends Component
{
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function render()
    {
        return app(StockNotificationController::class)->unsubscribe($this->token);
    }
}


