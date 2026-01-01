<?php

namespace App\Livewire\Frontend\Pages;

use App\Models\ComingSoonNotification;
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
        $notification = ComingSoonNotification::where('token', $this->token)->firstOrFail();
        $product = $notification->product;

        $notification->delete();

        return view('frontend.coming-soon.unsubscribed', [
            'product' => $product,
        ]);
    }
}


