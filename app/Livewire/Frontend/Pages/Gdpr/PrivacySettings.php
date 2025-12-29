<?php

namespace App\Livewire\Frontend\Pages\Gdpr;

use App\Models\ConsentTracking;
use App\Models\CookieConsent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PrivacySettings extends Component
{
    public ?CookieConsent $consent = null;

    public Collection $consentHistory;

    public function mount(): void
    {
        $user = Auth::user();

        abort_if(! $user, 403);

        $customer = $user->customers()->first();

        $this->consent = CookieConsent::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $historyQuery = ConsentTracking::query()
            ->where('user_id', $user->id);

        if ($customer) {
            $historyQuery->orWhere('customer_id', $customer->id);
        }

        $this->consentHistory = $historyQuery
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.frontend.pages.gdpr.privacy-settings', [
            'consent' => $this->consent,
            'consentHistory' => $this->consentHistory,
        ])->layout('frontend.layout', [
            'pageTitle' => 'Privacy Settings',
            'mainClass' => 'max-w-none p-0',
        ]);
    }
}

