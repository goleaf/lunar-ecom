@extends('admin.layout')

@section('title', 'Product Badges')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Product Badges</h1>
            <p class="text-gray-600 mt-2">Manage product badges and labels</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.products.badges.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Create Badge
            </a>
            <button onclick="processAutoAssignment()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Process Auto-Assignment
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Badge</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auto-Assign</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($badges as $badge)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="{{ $badge->getCssClasses() }}" style="{{ $badge->getInlineStyles() }}">
                                    {{ $badge->getDisplayLabel() }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">
                                {{ ucfirst($badge->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ ucfirst(str_replace('-', ' ', $badge->position)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $badge->priority }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($badge->auto_assign)
                                <span class="text-green-600">Yes</span>
                            @else
                                <span class="text-gray-400">No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($badge->isActive())
                                <span class="text-green-600">Active</span>
                            @else
                                <span class="text-gray-400">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.products.badges.edit', $badge) }}" class="text-blue-600 hover:underline mr-2">Edit</a>
                            <button onclick="deleteBadge({{ $badge->id }})" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $badges->links() }}
    </div>
</div>

@push('scripts')
<script>
function deleteBadge(badgeId) {
    if (!confirm('Are you sure you want to delete this badge?')) return;
    
    fetch(`/admin/products/badges/${badgeId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete badge');
        }
    });
}

function processAutoAssignment() {
    if (!confirm('This will process auto-assignment for all products. Continue?')) return;
    
    fetch('{{ route('admin.products.badges.process-auto-assignment') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Processed ${data.processed} products`);
        } else {
            alert('Failed to process auto-assignment: ' + data.message);
        }
    });
}
</script>
@endpush
@endsection

