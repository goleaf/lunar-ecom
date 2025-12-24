<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lunar\Facades\StorefrontSession;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Symfony\Component\HttpFoundation\Response;

class StorefrontSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Initialize the storefront session with channel, currency, and language.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Initialize channel (default to webstore)
        $channel = Channel::where('handle', 'webstore')->first();
        if ($channel) {
            StorefrontSession::setChannel($channel);
        }

        // Initialize currency (default to USD)
        $currency = Currency::where('code', 'USD')->first();
        if ($currency) {
            StorefrontSession::setCurrency($currency);
        }

        // Initialize customer groups
        StorefrontSession::initCustomerGroups();

        // Initialize customer (if logged in)
        StorefrontSession::initCustomer();

        return $next($request);
    }
}

