<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\PriceHistoryResource as FilamentPriceHistoryResource;
use App\Http\Controllers\Controller;
use App\Models\PriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PricingHistoryController extends Controller
{
    /**
     * Display pricing history.
     */
    public function index(Request $request)
    {
        // Prefer Filament for the admin UI.
        return redirect()->route('filament.admin.resources.' . FilamentPriceHistoryResource::getSlug() . '.index', $request->query());
    }
}


