<?php

namespace App\Livewire\Frontend\Pages;

use App\Models\Bundle;
use Livewire\Component;

class BundlesIndex extends Component
{
    public function render()
    {
        $bundles = Bundle::with(['product.media', 'items'])
            ->active()
            ->orderBy('display_order')
            ->paginate(12);

        return view('frontend.bundles.index', compact('bundles'));
    }
}


