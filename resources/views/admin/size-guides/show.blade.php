@extends('admin.layout')

@section('title', 'Size Guide Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">{{ $sizeGuide->name }}</h2>
            <p class="text-sm text-slate-600">{{ $sizeGuide->description }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.size-guides.edit', $sizeGuide) }}" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Edit guide</a>
            <form method="POST" action="{{ route('admin.size-guides.destroy', $sizeGuide) }}" onsubmit="return confirm('Delete this size guide?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm border border-red-200 text-red-600 rounded hover:bg-red-50">Delete</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-xs text-slate-500">Category</div>
            <div class="font-semibold">{{ $sizeGuide->category?->translateAttribute('name') ?? 'All' }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-500">Brand</div>
            <div class="font-semibold">{{ $sizeGuide->brand?->name ?? 'All' }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-500">System</div>
            <div class="font-semibold">{{ strtoupper($sizeGuide->size_system) }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-500">Unit</div>
            <div class="font-semibold">{{ $sizeGuide->measurement_unit }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-500">Region</div>
            <div class="font-semibold">{{ $sizeGuide->region ?? 'N/A' }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-500">Status</div>
            <div class="font-semibold">{{ $sizeGuide->is_active ? 'Active' : 'Inactive' }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Size charts</h3>
        @if($sizeGuide->sizeCharts->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Size</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Measurements</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Range</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sizeGuide->sizeCharts as $chart)
                            <tr>
                                <td class="px-4 py-3">{{ $chart->size_name }}</td>
                                <td class="px-4 py-3">{{ json_encode($chart->measurements ?? []) }}</td>
                                <td class="px-4 py-3">{{ $chart->size_min }} - {{ $chart->size_max }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">No size charts defined.</p>
        @endif
    </div>
</div>
@endsection
