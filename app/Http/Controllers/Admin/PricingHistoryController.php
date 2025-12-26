<?php

namespace App\Http\Controllers\Admin;

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
        $filters = $request->only(['product_id', 'product_variant_id', 'change_type', 'start_date', 'end_date']);

        $history = PriceHistory::query()
            ->when($filters['product_id'] ?? null, function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            })
            ->when($filters['product_variant_id'] ?? null, function ($q) use ($filters) {
                $q->where('product_variant_id', $filters['product_variant_id']);
            })
            ->when($filters['change_type'] ?? null, function ($q) use ($filters) {
                $q->where('change_type', $filters['change_type']);
            })
            ->when($filters['start_date'] ?? null, function ($q) use ($filters) {
                $q->where('changed_at', '>=', $filters['start_date']);
            })
            ->when($filters['end_date'] ?? null, function ($q) use ($filters) {
                $q->where('changed_at', '<=', $filters['end_date']);
            })
            ->with(['product', 'productVariant', 'priceMatrix', 'changedBy'])
            ->orderBy('changed_at', 'desc')
            ->paginate(50);

        return view('admin.pricing.history', compact('history', 'filters'));
    }
}


