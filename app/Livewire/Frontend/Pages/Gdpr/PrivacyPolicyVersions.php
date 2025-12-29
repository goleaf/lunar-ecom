<?php

namespace App\Livewire\Frontend\Pages\Gdpr;

use App\Models\PrivacyPolicy;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class PrivacyPolicyVersions extends Component
{
    public Collection $policies;

    public function mount(): void
    {
        $this->policies = PrivacyPolicy::active()
            ->orderBy('effective_date', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.frontend.pages.gdpr.privacy-policy-versions', [
            'policies' => $this->policies,
        ])->layout('frontend.layout', [
            'pageTitle' => 'Privacy Policy Versions',
            'mainClass' => 'max-w-none p-0',
        ]);
    }
}

