<?php

namespace App\View\Components\Frontend;

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
        return view('frontend.collection-card');
    }
}




