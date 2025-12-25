<?php

namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;
use App\Models\CookieConsent;
use App\Models\ConsentTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

        $user = Auth::user();
        $customer = $user ? $user->customers()->first() : null;
        $sessionId = session()->getId();

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
        $this->trackConsents($validated, $user, $customer, $sessionId);

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
    protected function trackConsents(array $consents, $user, $customer, string $sessionId): void
    {
        $consentTypes = [
            'analytics' => ConsentTracking::TYPE_ANALYTICS,
            'marketing' => ConsentTracking::TYPE_MARKETING,
            'preferences' => ConsentTracking::TYPE_PREFERENCES,
        ];

        foreach ($consentTypes as $key => $type) {
            if (isset($consents[$key])) {
                ConsentTracking::recordConsent(
                    $type,
                    "Cookie consent for {$key} category",
                    $consents[$key],
                    $user?->id,
                    $customer?->id,
                    $sessionId,
                    ConsentTracking::METHOD_SETTINGS
                );
            }
        }
    }
}
