@extends('admin.layout')

@section('title', 'Checkout Lock Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Checkout lock #{{ $checkoutLock->id }}</h2>
            <p class="text-sm text-slate-600">State: {{ str_replace('_', ' ', $checkoutLock->state) }}</p>
        </div>
        <form method="POST" action="{{ route('admin.checkout-locks.release', $checkoutLock) }}" onsubmit="return confirm('Release this lock?')">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm border border-red-200 text-red-600 rounded hover:bg-red-50">Release lock</button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-5 space-y-3">
            <div>
                <div class="text-xs uppercase text-slate-500">User</div>
                <div class="text-sm">{{ $checkoutLock->user?->email ?? 'Guest' }}</div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Cart</div>
                <div class="text-sm">{{ $checkoutLock->cart_id ?? 'N/A' }}</div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Phase</div>
                <div class="text-sm">{{ $checkoutLock->phase ?? 'N/A' }}</div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Created</div>
                <div class="text-sm">{{ $checkoutLock->created_at?->format('M j, Y H:i') }}</div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Expires</div>
                <div class="text-sm">{{ $checkoutLock->expires_at?->format('M j, Y H:i') }}</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
            <h3 class="text-lg font-semibold mb-4">Order summary</h3>
            @if($order)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-xs uppercase text-slate-500">Order ID</div>
                        <div class="font-semibold">#{{ $order->id }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-slate-500">Placed</div>
                        <div>{{ $order->placed_at?->format('M j, Y H:i') ?? 'Pending' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-slate-500">Total</div>
                        <div>{{ $order->total ?? 'N/A' }}</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-500">No order created yet.</p>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold mb-4">Cart lines</h3>
        @if($checkoutLock->cart && $checkoutLock->cart->lines->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Item</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($checkoutLock->cart->lines as $line)
                            <tr>
                                <td class="px-4 py-3">{{ $line->purchasable?->product?->translateAttribute('name') ?? 'Item' }}</td>
                                <td class="px-4 py-3">{{ $line->purchasable?->sku ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $line->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">No cart lines found.</p>
        @endif
    </div>
</div>
@endsection
