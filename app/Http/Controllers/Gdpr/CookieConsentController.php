<?php

namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;
use App\Models\CookieConsent;
use App\Models\ConsentTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Lunar\Models\Customer;

class CookieConsentController extends Controller
{
    /**
     * Store or update cookie consent
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'necessary' => 'boolean',
            'analytics' => 'boolean',
            'marketing' => 'boolean',
            'preferences' => 'boolean',
            'custom_categories' => 'array',
            'consent_method' => 'nullable|string|in:banner,settings,api,import',
        ]);

        $user = Auth::user();
        $customer = $user ? $user->customers()->first() : null;
        $sessionId = session()->getId();
        $consentMethod = $validated['consent_method'] ?? ConsentTracking::METHOD_BANNER;

        // Find or create consent record
        $consent = CookieConsent::updateOrCreate(
            [
                'user_id' => $user?->id,
                'customer_id' => $customer?->id,
                'session_id' => $user ? null : $sessionId,
            ],
            [
                'necessary' => $validated['necessary'] ?? true,
                'analytics' => $validated['analytics'] ?? false,
                'marketing' => $validated['marketing'] ?? false,
                'preferences' => $validated['preferences'] ?? false,
                'custom_categories' => $validated['custom_categories'] ?? [],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'consented_at' => now(),
                'last_updated_at' => now(),
            ]
        );

        // Track individual consents
        $this->trackConsents($consent, $user, $customer, $sessionId, $consentMethod);

        $minutes = now()->addDays(365)->diffInMinutes();
        Cookie::queue(Cookie::make('cookie_consent', json_encode([
            'necessary' => true,
            'analytics' => (bool) $consent->analytics,
            'marketing' => (bool) $consent->marketing,
            'preferences' => (bool) $consent->preferences,
            'updated_at' => now()->toIso8601String(),
        ]), $minutes));

        if (! $request->expectsJson() && ! $request->wantsJson()) {
            return redirect()->back();
        }

        return response()->json([
            'success' => true,
            'consent' => $consent,
            'message' => 'Cookie preferences saved successfully',
        ]);
    }

    /**
     * Get current consent status
     */
    public function show(Request $request)
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
            return response()->json([
                'consent' => null,
                'has_consented' => false,
            ]);
        }

        return response()->json([
            'consent' => $consent,
            'has_consented' => true,
            'categories' => $consent->getConsentedCategories(),
        ]);
    }

    /**
     * Update consent preferences
     */
    public function update(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Track individual consent types
     */
    protected function trackConsents(
        CookieConsent $consent,
        $user,
        $customer,
        string $sessionId,
        string $method
    ): void
    {
        $consentTypes = [
            'analytics' => ConsentTracking::TYPE_ANALYTICS,
            'marketing' => ConsentTracking::TYPE_MARKETING,
            'preferences' => ConsentTracking::TYPE_PREFERENCES,
        ];

        foreach ($consentTypes as $key => $type) {
            ConsentTracking::recordConsent(
                $type,
                "Cookie consent for {$key} category",
                (bool) $consent->{$key},
                $user?->id,
                $customer?->id,
                $user ? null : $sessionId,
                $method
            );
        }
    }
}
