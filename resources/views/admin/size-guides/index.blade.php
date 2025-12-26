@extends('admin.layout')

@section('title', 'Size Guides')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Size guides</h2>
            <p class="text-sm text-slate-600">Configure fit guidance for categories and brands.</p>
        </div>
        <a href="{{ route('admin.size-guides.create') }}" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Create guide</a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Guide</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Brand</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($sizeGuides as $guide)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $guide->name }}</div>
                            <div class="text-xs text-slate-500">{{ $guide->size_system }} | {{ $guide->measurement_unit }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $guide->category?->translateAttribute('name') ?? 'All' }}</td>
                        <td class="px-4 py-3">{{ $guide->brand?->name ?? 'All' }}</td>
                        <td class="px-4 py-3">{{ $guide->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.size-guides.show', $guide) }}" class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('admin.size-guides.edit', $guide) }}" class="ml-3 text-slate-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No size guides yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $sizeGuides->links() }}
    </div>
</div>
@endsection
