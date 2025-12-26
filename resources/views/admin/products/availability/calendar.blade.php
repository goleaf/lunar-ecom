@extends('admin.layout')

@section('title', 'Availability Calendar')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Availability calendar</h2>
        <p class="text-sm text-slate-600">Product: {{ $product->translateAttribute('name') }} | Month: {{ $month }}</p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Available qty</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($availability as $slot)
                    <tr>
                        <td class="px-4 py-3">{{ $slot['date'] }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('-', ' ', $slot['status'] ?? 'unknown')) }}</td>
                        <td class="px-4 py-3">{{ $slot['available_quantity'] ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $slot['reason'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No availability data for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold mb-4">Bookings</h3>
        @if($bookings->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Dates</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($bookings as $booking)
                            <tr>
                                <td class="px-4 py-3">{{ $booking->customer?->name ?? 'Guest' }}</td>
                                <td class="px-4 py-3">
                                    {{ $booking->start_date?->format('M j, Y') }}
                                    @if($booking->end_date)
                                        - {{ $booking->end_date->format('M j, Y') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ ucfirst($booking->status) }}</td>
                                <td class="px-4 py-3">{{ $booking->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">No bookings in this period.</p>
        @endif
    </div>
</div>
@endsection
