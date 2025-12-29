<?php

namespace App\Livewire\Frontend\Pages\Gdpr;

use App\Models\PrivacyPolicy;
use Livewire\Component;

class PrivacyPolicyShow extends Component
{
    public PrivacyPolicy $policy;

    public function mount(): void
    {
        $this->policy = PrivacyPolicy::current()->firstOrFail();
    }

    public function render()
    {
        return view('livewire.frontend.pages.gdpr.privacy-policy', [
            'policy' => $this->policy,
        ])->layout('frontend.layout', [
            'pageTitle' => $this->policy->title ?: 'Privacy Policy',
            'mainClass' => 'max-w-none p-0',
        ]);
    }
}

