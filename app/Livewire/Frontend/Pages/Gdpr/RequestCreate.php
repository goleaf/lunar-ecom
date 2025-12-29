<?php

namespace App\Livewire\Frontend\Pages\Gdpr;

use Livewire\Component;

class RequestCreate extends Component
{
    public string $type = 'export';

    public function mount(): void
    {
        $type = (string) request()->query('type', 'export');

        $allowed = ['export', 'deletion', 'anonymization', 'rectification'];

        $this->type = in_array($type, $allowed, true) ? $type : 'export';
    }

    public function render()
    {
        return view('livewire.frontend.pages.gdpr.request-form', [
            'type' => $this->type,
        ])->layout('frontend.layout', [
            'pageTitle' => 'GDPR Request',
            'mainClass' => 'max-w-none p-0',
        ]);
    }
}

