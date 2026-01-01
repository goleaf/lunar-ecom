<?php

namespace App\Livewire\Frontend\Pages;

use App\Models\Bundle;
use App\Services\BundleService;
use Livewire\Component;
use Lunar\Facades\StorefrontSession;
use Lunar\Models\Currency;

class BundleShow extends Component
{
    public Bundle $bundle;

    public function mount(Bundle $bundle): void
    {
        $this->bundle = $bundle;
    }

    public function render()
    {
        $bundle = $this->bundle;
        $bundle->load(['product.media', 'items.product.variants', 'items.productVariant']);

        $bundleService = app(BundleService::class);

        // Track view
        $bundle->incrementView();
        $bundleService->trackBundleEvent($bundle, 'view');

        $request = request();
        $selectedItems = $request->input('selected_items');
        $quantity = (int) $request->input('quantity', 1);

        $pricing = $bundleService->calculateBundlePrice($bundle, $selectedItems, $quantity);
        $availability = $bundleService->validateBundleAvailability($bundle, $selectedItems, $quantity);

        $currency = Currency::getDefault();
        $customerGroupId = StorefrontSession::getCustomerGroups()->first()?->id;
        $individualTotal = $pricing['original_price'] ?? $bundle->calculateIndividualTotal($currency, $customerGroupId);
        $bundlePrice = $pricing['bundle_price'] ?? $bundle->calculatePrice($currency, $customerGroupId, $quantity);
        $savings = $pricing['savings_amount'] ?? $bundle->calculateSavings($currency, $customerGroupId);
        $availableStock = $bundle->getAvailableStock();

        $availableProducts = null;
        if ($bundle->isDynamic() && $bundle->category_id) {
            $availableProducts = $bundleService->getAvailableProductsForBundle($bundle);
        }

        return view('frontend.bundles.show', compact(
            'bundle',
            'currency',
            'individualTotal',
            'bundlePrice',
            'savings',
            'availableStock',
            'pricing',
            'availability',
            'availableProducts'
        ));
    }
}


