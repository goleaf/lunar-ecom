@extends('admin.layout')

@section('title', 'Pricing History')

@section('content')
@php
    $productRoute = request()->route('product');
    $productId = is_object($productRoute) ? $productRoute->id : $productRoute;
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Pricing history</h2>
            <p class="text-sm text-slate-600">Audit recent price changes and compliance history.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.products.pricing.import.index', ['product' => $productId]) }}" class="px-4 py-2 text-sm border border-slate-300 rounded hover:bg-slate-50">Import pricing</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('admin.products.pricing.history', ['product' => $productId]) }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs text-slate-600 mb-1">Product ID</label>
                <input type="text" name="product_id" value="{{ $filters['product_id'] ?? '' }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Variant ID</label>
                <input type="text" name="product_variant_id" value="{{ $filters['product_variant_id'] ?? '' }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Change type</label>
                <input type="text" name="change_type" value="{{ $filters['change_type'] ?? '' }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Start date</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">End date</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-5">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Apply filters</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Variant</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Price</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Compare at</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Effective</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Changed by</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($history as $entry)
                    <tr>
                        <td class="px-4 py-3">#{{ $entry->product_variant_id }}</td>
                        <td class="px-4 py-3">{{ $entry->price }}</td>
                        <td class="px-4 py-3">{{ $entry->compare_at_price ?? 'N/A' }}</td>
                        <td class="px-4 py-3">
                            {{ optional($entry->effective_from)->format('M j, Y') ?? 'N/A' }}
                            @if($entry->effective_to)
                                - {{ $entry->effective_to->format('M j, Y') }}
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $entry->changedBy?->email ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No pricing history found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $history->links() }}
    </div>
</div>
@endsection
