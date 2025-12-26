<?php

namespace App\Services;

use App\Models\DeviceFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Device Fingerprint Service
 * 
 * Creates lightweight device fingerprints for fraud detection.
 */
class DeviceFingerprintService
{
    /**
     * Generate device fingerprint from request.
     */
    public function generateFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->ip(),
        ];

        // Add screen resolution if available (from JavaScript)
        if ($request->has('screen_resolution')) {
            $components[] = $request->input('screen_resolution');
        }

        // Add timezone if available (from JavaScript)
        if ($request->has('timezone')) {
            $components[] = $request->input('timezone');
        }

        // Create hash
        $fingerprint = hash('sha256', implode('|', array_filter($components)));

        return $fingerprint;
    }

    /**
     * Store device fingerprint.
     */
    public function storeFingerprint(string $fingerprintHash, Request $request): DeviceFingerprint
    {
        return DeviceFingerprint::firstOrCreate(
            ['fingerprint_hash' => $fingerprintHash],
            [
                'user_agent_hash' => hash('sha256', $request->userAgent()),
                'screen_resolution' => $request->input('screen_resolution'),
                'timezone' => $request->input('timezone'),
                'language' => $request->header('Accept-Language'),
                'metadata' => [
                    'ip' => $request->ip(),
                    'accept_encoding' => $request->header('Accept-Encoding'),
                    'created_at' => now()->toIso8601String(),
                ],
            ]
        );
    }

    /**
     * Get device fingerprint from request.
     */
    public function getFingerprint(Request $request): ?string
    {
        // Try to get from request (set by JavaScript)
        if ($request->has('device_fingerprint')) {
            return $request->input('device_fingerprint');
        }

        // Generate from request data
        return $this->generateFingerprint($request);
    }
}


