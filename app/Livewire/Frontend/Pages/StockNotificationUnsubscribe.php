<?php

namespace App\Livewire\Frontend\Pages;

use App\Services\StockNotificationService;
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
        $success = app(StockNotificationService::class)->unsubscribe($this->token);

        return view('frontend.stock-notifications.unsubscribe', [
            'success' => $success,
        ]);
    }
}


