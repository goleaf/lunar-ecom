<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lunar\Facades\StorefrontSession;
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
}

