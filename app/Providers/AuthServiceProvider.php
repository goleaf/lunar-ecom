<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Collection;
use App\Models\User;
use App\Policies\ProductPolicy;
use App\Policies\ProductVariantPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CollectionPolicy;
use App\Policies\AddressPolicy;
use App\Policies\UserPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductWorkflowPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Lunar\Models\Address;
use Lunar\Models\Order;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        ProductVariant::class => ProductVariantPolicy::class,
        Category::class => CategoryPolicy::class,
        Collection::class => CollectionPolicy::class,
        Address::class => AddressPolicy::class,
        User::class => UserPolicy::class,
        Order::class => OrderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

