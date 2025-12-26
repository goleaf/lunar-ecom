<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\LanguageDetectionMiddleware::class,
            \App\Http\Middleware\FrontendSessionMiddleware::class,
            \App\Http\Middleware\TrackProductView::class,
            \App\Http\Middleware\TrackReferralLink::class,
        ]);
        
        // Register checkout cart protection middleware
        $middleware->alias([
            'protect.checkout.cart' => \App\Http\Middleware\ProtectCheckoutCart::class,
            'throttle.checkout' => \App\Http\Middleware\ThrottleCheckout::class,
            'rate.limit.checkout' => \App\Http\Middleware\RateLimitCheckout::class,
            'idempotent' => \App\Http\Middleware\IdempotentRequest::class,
            'http.cache' => \App\Http\Middleware\HttpCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
