@extends('admin.layout')

@section('title', 'Schedule History')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Schedule history</h2>
        <p class="text-sm text-slate-600">Execution log for product schedules.</p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Executed at</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Executed by</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($history as $entry)
                    <tr>
                        <td class="px-4 py-3">{{ $entry->product?->translateAttribute('name') ?? 'Product' }}</td>
                        <td class="px-4 py-3">{{ ucfirst($entry->productSchedule?->type ?? 'unknown') }}</td>
                        <td class="px-4 py-3">{{ $entry->executed_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3">{{ ucfirst($entry->status) }}</td>
                        <td class="px-4 py-3">{{ $entry->executedBy?->email ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No schedule history found.</td>
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
