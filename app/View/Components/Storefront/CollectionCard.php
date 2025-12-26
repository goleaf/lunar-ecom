<?php

namespace App\View\Components\Storefront;

use Illuminate\View\Component;

class CollectionCard extends Component
{
    public $collection;

    /**
     * Create a new component instance.
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('storefront.collection-card');
    }
}


