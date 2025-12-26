<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Disposable Email Service
 * 
 * Checks if an email address is from a disposable/temporary email service.
 */
class DisposableEmailService
{
    protected const CACHE_TTL = 86400; // 24 hours
    protected const API_URL = 'https://api.mailcheck.ai/v1/email/check';

    /**
     * Check if email is disposable.
     */
    public function isDisposable(string $email): bool
    {
        $domain = $this->extractDomain($email);
        
        if (!$domain) {
            return false;
        }

        // Check cache first
        $cacheKey = "disposable_email_{$domain}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        // Check against known disposable domains list
        $isDisposable = $this->checkKnownDomains($domain);

        // Optionally check via API (requires API key)
        if (!$isDisposable && config('referral.fraud.disposable_email_api_key')) {
            $isDisposable = $this->checkViaApi($email);
        }

        // Cache result
        Cache::put($cacheKey, $isDisposable, self::CACHE_TTL);

        return $isDisposable;
    }

    /**
     * Extract domain from email.
     */
    protected function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? null;
    }

    /**
     * Check against known disposable domains.
     */
    protected function checkKnownDomains(string $domain): bool
    {
        $knownDomains = [
            'tempmail.com',
            'guerrillamail.com',
            'mailinator.com',
            '10minutemail.com',
            'throwaway.email',
            'temp-mail.org',
            'getnada.com',
            'mohmal.com',
            'yopmail.com',
            'sharklasers.com',
            // Add more as needed
        ];

        return in_array(strtolower($domain), $knownDomains);
    }

    /**
     * Check via API.
     */
    protected function checkViaApi(string $email): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('referral.fraud.disposable_email_api_key'),
            ])->get(self::API_URL, [
                'email' => $email,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['disposable'] ?? false;
            }
        } catch (\Exception $e) {
            // Log error but don't block
            \Log::warning('Disposable email API check failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}


