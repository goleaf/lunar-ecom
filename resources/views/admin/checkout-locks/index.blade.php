@extends('admin.layout')

@section('title', 'Checkout Locks')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Checkout locks</h2>
            <p class="text-sm text-slate-600">Monitor in-progress checkouts and lock states.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('admin.checkout-locks.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">State</label>
                <select name="state" class="rounded border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All</option>
                    @foreach(['pending', 'validating', 'reserving', 'locking_prices', 'authorizing', 'creating_order', 'capturing', 'committing', 'completed', 'failed'] as $state)
                        <option value="{{ $state }}" {{ request('state') === $state ? 'selected' : '' }}>{{ str_replace('_', ' ', $state) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Date from</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Date to</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">State</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Cart</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">User</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Created</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Expires</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($locks as $lock)
                    @php
                        $status = $lock->isCompleted() ? 'Completed' : ($lock->isFailed() ? 'Failed' : ($lock->isExpired() ? 'Expired' : 'Active'));
                    @endphp
                    <tr>
                        <td class="px-4 py-3">#{{ $lock->id }}</td>
                        <td class="px-4 py-3">{{ str_replace('_', ' ', $lock->state) }}</td>
                        <td class="px-4 py-3">{{ $lock->cart_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $lock->user?->email ?? 'Guest' }}</td>
                        <td class="px-4 py-3">{{ $lock->created_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $lock->expires_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $status === 'Active' ? 'bg-emerald-100 text-emerald-800' : ($status === 'Failed' ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ $status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.checkout-locks.show', $lock) }}" class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">No checkout locks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $locks->links() }}
    </div>
</div>
@endsection
