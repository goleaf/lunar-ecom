<?php

namespace App\View\Components\Frontend;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class Header extends Component
{
    public EloquentCollection $navCategories;

    public function __construct()
    {
        // Root navigation categories (marketplace-style menu).
        $this->navCategories = Category::query()
            ->active()
            ->inNavigation()
            ->whereNull('parent_id')
            ->ordered()
            ->with([
                'children' => function ($query) {
                    $query
                        ->active()
                        ->inNavigation()
                        ->ordered()
                        ->limit(12);
                },
            ])
            ->limit(10)
            ->get();
    }

    public function render(): View
    {
        return view('components.frontend.header');
    }
}

