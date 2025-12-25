<?php

namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;
use App\Models\CookieConsent;
use App\Models\ConsentTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivacySettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show privacy settings page
     */
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customers()->first();

        $consent = CookieConsent::where(function ($query) use ($user, $customer) {
            if ($user) {
                $query->where('user_id', $user->id);
            } elseif ($customer) {
                $query->where('customer_id', $customer->id);
            }
        })->latest()->first();

        $consentHistory = ConsentTracking::where('user_id', $user->id)
            ->orWhere('customer_id', $customer?->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('gdpr.privacy-settings', [
            'consent' => $consent,
            'consentHistory' => $consentHistory,
        ]);
    }

    /**
     * Update privacy settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'analytics' => 'boolean',
            'marketing' => 'boolean',
            'preferences' => 'boolean',
        ]);

        $user = Auth::user();
        $customer = $user->customers()->first();

        $consent = CookieConsent::updateOrCreate(
            [
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
            ],
            [
                'necessary' => true, // Always required
                'analytics' => $validated['analytics'] ?? false,
                'marketing' => $validated['marketing'] ?? false,
                'preferences' => $validated['preferences'] ?? false,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'consented_at' => now(),
                'last_updated_at' => now(),
            ]
        );

        // Track consent changes
        foreach (['analytics', 'marketing', 'preferences'] as $type) {
            if (isset($validated[$type])) {
                ConsentTracking::recordConsent(
                    match ($type) {
                        'analytics' => ConsentTracking::TYPE_ANALYTICS,
                        'marketing' => ConsentTracking::TYPE_MARKETING,
                        'preferences' => ConsentTracking::TYPE_PREFERENCES,
                    },
                    "Privacy settings updated for {$type}",
                    $validated[$type],
                    $user->id,
                    $customer?->id,
                    session()->getId(),
                    ConsentTracking::METHOD_SETTINGS
                );
            }
        }

        return redirect()->route('gdpr.privacy-settings')
            ->with('success', 'Privacy settings updated successfully');
    }
}
