<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Brands\BrandHelper;
use App\Services\SEOService;
use Illuminate\Http\Request;
use Livewire\Component;

class BrandsIndex extends Component
{
    public ?string $letter = null;

    public function mount(Request $request): void
    {
        $letter = $request->get('letter');
        $this->letter = is_string($letter) && $letter !== '' ? $letter : null;
    }

    public function render()
    {
        $letter = $this->letter;

        if ($letter) {
            $brands = BrandHelper::getByLetter($letter);
            $groupedBrands = [$letter => $brands];
        } else {
            $groupedBrands = BrandHelper::getGroupedByLetter();
        }

        $availableLetters = BrandHelper::getAvailableLetters();
        $allBrands = BrandHelper::getAll();

        $metaTags = SEOService::getDefaultMetaTags(
            'Brands',
            'Browse all brands. Find products from your favorite manufacturers.',
            null,
            request()->url()
        );

        return view('frontend.brands.index', compact(
            'groupedBrands',
            'availableLetters',
            'allBrands',
            'letter',
            'metaTags'
        ));
    }
}


