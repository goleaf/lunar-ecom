<?php

namespace App\Livewire\Frontend;

use App\Models\ConsentTracking;
use App\Models\CookieConsent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

class CookieConsentBanner extends Component
{
    public bool $show = false;

    public bool $necessary = true;

    public bool $analytics = false;

    public bool $marketing = false;

    public bool $preferences = false;

    public bool $showDetails = false;

    public function mount(): void
    {
        $user = Auth::user();
        $customer = $user ? $user->customers()->first() : null;
        $sessionId = session()->getId();

        $consent = CookieConsent::where(function ($query) use ($user, $customer, $sessionId) {
            if ($user) {
                $query->where('user_id', $user->id);
            } elseif ($customer) {
                $query->where('customer_id', $customer->id);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->latest()->first();

        if (!$consent) {
            $this->show = true;
            return;
        }

        $this->show = false;
        $this->necessary = (bool) $consent->necessary;
        $this->analytics = (bool) $consent->analytics;
        $this->marketing = (bool) $consent->marketing;
        $this->preferences = (bool) $consent->preferences;
    }

    public function acceptAll(): void
    {
        $this->analytics = true;
        $this->marketing = true;
        $this->preferences = true;

        $this->save();
    }

    public function acceptNecessary(): void
    {
        $this->analytics = false;
        $this->marketing = false;
        $this->preferences = false;

        $this->save();
    }

    public function save(): void
    {
        $user = Auth::user();
        $customer = $user ? $user->customers()->first() : null;
        $sessionId = session()->getId();

        $consent = CookieConsent::updateOrCreate(
            [
                'user_id' => $user?->id,
                'customer_id' => $customer?->id,
                'session_id' => $user ? null : $sessionId,
            ],
            [
                'necessary' => true,
                'analytics' => $this->analytics,
                'marketing' => $this->marketing,
                'preferences' => $this->preferences,
                'custom_categories' => [],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'consented_at' => now(),
                'last_updated_at' => now(),
            ]
        );

        // Track consent changes for auditing.
        foreach ([
            'analytics' => ConsentTracking::TYPE_ANALYTICS,
            'marketing' => ConsentTracking::TYPE_MARKETING,
            'preferences' => ConsentTracking::TYPE_PREFERENCES,
        ] as $key => $type) {
            ConsentTracking::recordConsent(
                $type,
                "Cookie consent for {$key} category",
                (bool) $consent->{$key},
                $user?->id,
                $customer?->id,
                $user ? null : $sessionId,
                ConsentTracking::METHOD_BANNER
            );
        }

        // Persist a lightweight consent cookie for fast access client-side (optional).
        $minutes = now()->addDays(365)->diffInMinutes();
        Cookie::queue(Cookie::make('cookie_consent', json_encode([
            'necessary' => true,
            'analytics' => (bool) $consent->analytics,
            'marketing' => (bool) $consent->marketing,
            'preferences' => (bool) $consent->preferences,
            'updated_at' => now()->toIso8601String(),
        ]), $minutes));

        $this->show = false;

        // If other parts of the page conditionally load scripts, a hard reload keeps things deterministic.
        $this->redirect(request()->fullUrl(), navigate: true);
    }

    public function render()
    {
        return view('livewire.frontend.cookie-consent-banner');
    }
}


