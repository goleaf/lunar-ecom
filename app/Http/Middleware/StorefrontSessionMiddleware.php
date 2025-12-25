<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lunar\Facades\StorefrontSession;
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

        return $next($request);
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

