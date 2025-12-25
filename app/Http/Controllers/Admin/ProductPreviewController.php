<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Controller for previewing products in storefront view.
 */
class ProductPreviewController extends Controller
{
    /**
     * Preview product in storefront view.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return \Illuminate\View\View
     */
    public function preview(Request $request, Product $product)
    {
        // Set preview mode in session
        session(['preview_mode' => true]);
        
        return redirect()->route('storefront.products.show', [
            'product' => $product->slug ?? $product->id,
        ]);
    }
}

