@extends('admin.layout')

@section('title', 'Import Report')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Import Report</h1>
            <a href="{{ route('admin.products.import.index') }}" class="text-indigo-600 hover:text-indigo-900">
                ← Back to Imports
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Import Summary</h2>
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded">
                    <div class="text-2xl font-bold text-blue-600">{{ $import->total_rows }}</div>
                    <div class="text-sm text-gray-600">Total Rows</div>
                </div>
                <div class="bg-green-50 p-4 rounded">
                    <div class="text-2xl font-bold text-green-600">{{ $import->successful_rows }}</div>
                    <div class="text-sm text-gray-600">Successful</div>
                </div>
                <div class="bg-red-50 p-4 rounded">
                    <div class="text-2xl font-bold text-red-600">{{ $import->failed_rows }}</div>
                    <div class="text-sm text-gray-600">Failed</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded">
                    <div class="text-2xl font-bold text-yellow-600">{{ $import->skipped_rows }}</div>
                    <div class="text-sm text-gray-600">Skipped</div>
                </div>
            </div>
        </div>

        @if(count($failed_rows) > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Failed Rows</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Row #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Errors</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($failed_rows as $row)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['row_number'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['sku'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-red-600">
                                        @if(isset($row['errors']))
                                            <ul class="list-disc list-inside">
                                                @foreach($row['errors'] as $field => $error)
                                                    <li><strong>{{ $field }}:</strong> {{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if(isset($row['error_message']))
                                            <p>{{ $row['error_message'] }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($import->canRollback())
            <div class="mt-6 text-center">
                <button onclick="rollbackImport({{ $import->id }})" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700">
                    Rollback Import
                </button>
            </div>
        @endif
    </div>
</div>

<script>
function rollbackImport(importId) {
    if (!confirm('Are you sure you want to rollback this import? This will delete all products created by this import.')) {
        return;
    }
    
    fetch(`/admin/products/import/${importId}/rollback`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Rollback completed. ${data.rolled_back} products rolled back.`);
            window.location.href = '{{ route("admin.products.import.index") }}';
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
@endsection


