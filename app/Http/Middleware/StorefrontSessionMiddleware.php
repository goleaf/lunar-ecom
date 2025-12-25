<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lunar\Facades\StorefrontSession;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Symfony\Component\HttpFoundation\Response;

class StorefrontSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Initialize the storefront session with channel, currency, customer groups, and customer.
     * 
     * See: https://docs.lunarphp.com/1.x/storefront-utils/storefront-session
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure a default channel exists before initializing
        $this->ensureDefaultChannel();

        // Ensure a default currency exists before initializing
        $this->ensureDefaultCurrency();

        // Ensure a default customer group exists before initializing
        $this->ensureDefaultCustomerGroup();

        // Initialize channel (sets based on session or uses default)
        StorefrontSession::initChannel();

        // Initialize currency (sets based on session or uses default)
        StorefrontSession::initCurrency();

        // Initialize customer groups (sets based on session or uses default)
        StorefrontSession::initCustomerGroups();

        // Initialize customer (sets based on session or retrieves from logged-in user)
        StorefrontSession::initCustomer();

        // Initialize language (sets based on session or uses default)
        \App\Lunar\StorefrontSession\StorefrontSessionHelper::initLanguage();

        return $next($request);
    }

    /**
     * Ensure a default channel exists.
     * 
     * This prevents errors when Channel::getDefault() returns null.
     */
    protected function ensureDefaultChannel(): void
    {
        $defaultChannel = Channel::where('default', true)->first();

        if (!$defaultChannel) {
            // Check if any channel exists
            $anyChannel = Channel::first();

            if ($anyChannel) {
                // Set the first existing channel as default
                $anyChannel->update(['default' => true]);
            } else {
                // Create a default channel if none exists
                Channel::create([
                    'name' => 'Webstore',
                    'handle' => 'webstore',
                    'default' => true,
                ]);
            }
        }
    }

    /**
     * Ensure a default currency exists.
     * 
     * This prevents errors when Currency::getDefault() returns null.
     */
    protected function ensureDefaultCurrency(): void
    {
        $defaultCurrency = Currency::where('default', true)->first();

        if (!$defaultCurrency) {
            // Check if any currency exists
            $anyCurrency = Currency::first();

            if ($anyCurrency) {
                // Set the first existing currency as default
                $anyCurrency->update(['default' => true]);
            } else {
                // Create a default currency if none exists
                Currency::create([
                    'code' => 'USD',
                    'name' => 'US Dollar',
                    'exchange_rate' => 1.0000,
                    'decimal_places' => 2,
                    'enabled' => true,
                    'default' => true,
                ]);
            }
        }
    }

    /**
     * Ensure a default customer group exists.
     * 
     * This prevents errors when CustomerGroup::getDefault() returns null.
     */
    protected function ensureDefaultCustomerGroup(): void
    {
        $defaultGroup = CustomerGroup::where('default', true)->first();

        if (!$defaultGroup) {
            // Check if any customer group exists
            $anyGroup = CustomerGroup::first();

            if ($anyGroup) {
                // Set the first existing group as default
                $anyGroup->update(['default' => true]);
            } else {
                // Create a default customer group if none exists
                CustomerGroup::create([
                    'name' => 'Retail',
                    'handle' => 'retail',
                    'default' => true,
                ]);
            }
        }
    }
}

