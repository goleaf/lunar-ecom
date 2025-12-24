<?php

namespace App\Providers;

use App\Services\CartSessionService;
use App\Services\CartManager;
use App\Contracts\CartManagerInterface;
use App\Contracts\CartSessionInterface;
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind cart session service
        $this->app->singleton(CartSessionService::class);
        $this->app->bind(CartSessionInterface::class, CartSessionService::class);
        
        // Bind cart manager service
        $this->app->singleton(CartManager::class);
        $this->app->bind(CartManagerInterface::class, CartManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}