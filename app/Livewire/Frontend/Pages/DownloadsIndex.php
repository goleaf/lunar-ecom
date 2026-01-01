<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Customers\CustomerHelper;
use App\Models\Download;
use Livewire\Component;

class DownloadsIndex extends Component
{
    public function render()
    {
        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();

        if (!$user) {
            abort(403, 'You must be logged in to view downloads.');
        }

        $customer = CustomerHelper::getOrCreateCustomerForUser($user);

        $downloads = Download::where('customer_id', $customer->id)
            ->with(['digitalProduct.productVariant.product', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('frontend.downloads.index', [
            'downloads' => $downloads,
        ]);
    }
}


