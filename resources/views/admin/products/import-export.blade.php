@extends('admin.layout')

@section('title', 'Product Import/Export')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Product Import/Export</h1>
        <p class="text-gray-600 mt-2">Import products from CSV or export existing products</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Import Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-semibold mb-4">Import Products</h2>
            
            <div class="mb-4">
                <a href="{{ route('admin.products.import-template') }}" 
                   class="text-blue-600 hover:underline text-sm">
                    Download CSV Template
                </a>
            </div>

            <form id="import-form" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div>
                    <label for="import-file" class="block text-sm font-medium text-gray-700 mb-2">
                        CSV File
                    </label>
                    <input type="file" 
                           id="import-file" 
                           name="file" 
                           accept=".csv,.txt"
                           required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Maximum file size: 10MB</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Field Mapping
                    </label>
                    <div id="field-mapping" class="space-y-2">
                        <!-- Field mapping will be populated by JavaScript -->
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="options[update_existing]" value="1" class="rounded">
                        <span class="ml-2 text-sm text-gray-700">Update existing products (by SKU)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="options[skip_errors]" value="1" class="rounded">
                        <span class="ml-2 text-sm text-gray-700">Skip errors and continue</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Start Import
                </button>
            </form>

            <!-- Import Progress -->
            <div id="import-progress" class="hidden mt-6">
                <div class="mb-2 flex justify-between text-sm">
                    <span>Progress</span>
                    <span id="progress-text">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    <span id="processed-rows">0</span> / <span id="total-rows">0</span> rows processed
                </div>
                <div class="mt-2 text-sm">
                    <span class="text-green-600">Success: <span id="successful-rows">0</span></span>
                    <span class="text-red-600 ml-4">Failed: <span id="failed-rows">0</span></span>
                </div>
            </div>
        </div>

        <!-- Export Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-semibold mb-4">Export Products</h2>
            
            <form id="export-form" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Filters (Optional)
                    </label>
                    <div class="space-y-2">
                        <select name="filters[status]" class="w-full border rounded px-3 py-2">
                            <option value="">All Statuses</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                        <input type="text" 
                               name="filters[brand_id]" 
                               placeholder="Brand ID"
                               class="w-full border rounded px-3 py-2">
                        <input type="text" 
                               name="filters[category_id]" 
                               placeholder="Category ID"
                               class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Fields to Export
                    </label>
                    <div class="max-h-48 overflow-y-auto border rounded p-2">
                        @foreach($availableFields as $field => $label)
                            <label class="flex items-center py-1">
                                <input type="checkbox" 
                                       name="fields[]" 
                                       value="{{ $field }}" 
                                       checked
                                       class="rounded">
                                <span class="ml-2 text-sm">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" 
                        class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Export to CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Import History -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold mb-4">Import History</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Results</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($imports as $import)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $import->file_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                    ];
                                    $statusColor = $statusColors[$import->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 text-xs rounded {{ $statusColor }}">
                                    {{ ucfirst($import->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ number_format($import->getProgressPercentage(), 1) }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="text-green-600">{{ $import->successful_rows }}</span> /
                                <span class="text-red-600">{{ $import->failed_rows }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $import->created_at->format('M j, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($import->failed_rows > 0)
                                    <button onclick="showErrors({{ $import->id }})" 
                                            class="text-blue-600 hover:underline">
                                        View Errors
                                    </button>
                                @endif
                                @if(!$import->isCompleted())
                                    <button onclick="cancelImport({{ $import->id }})" 
                                            class="text-red-600 hover:underline ml-2">
                                        Cancel
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $imports->links() }}
        </div>
    </div>
</div>

<!-- Errors Modal -->
<div id="errors-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Import Errors</h3>
            <button onclick="closeErrorsModal()" class="text-gray-400 hover:text-gray-600">×</button>
        </div>
        <div id="errors-content" class="max-h-96 overflow-y-auto">
            <!-- Errors will be loaded here -->
        </div>
    </div>
</div>

@push('scripts')
<script>
const availableFields = @json($availableFields);

// Initialize field mapping on file upload
document.getElementById('import-file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Read CSV header
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split('\n');
        const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
        
        // Populate field mapping
        const mappingDiv = document.getElementById('field-mapping');
        mappingDiv.innerHTML = '';
        
        headers.forEach(header => {
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            
            const label = document.createElement('label');
            label.className = 'text-sm text-gray-700 w-32';
            label.textContent = header;
            
            const select = document.createElement('select');
            select.name = `field_mapping[${header}]`;
            select.className = 'flex-1 border rounded px-2 py-1 text-sm';
            
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '-- Skip --';
            select.appendChild(emptyOption);
            
            Object.entries(availableFields).forEach(([field, label]) => {
                const option = document.createElement('option');
                option.value = field;
                option.textContent = label;
                select.appendChild(option);
            });
            
            div.appendChild(label);
            div.appendChild(select);
            mappingDiv.appendChild(div);
        });
    };
    reader.readAsText(file);
});

// Handle import form submission
document.getElementById('import-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Starting Import...';
    
    try {
        const response = await fetch('{{ route('admin.products.import') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('import-progress').classList.remove('hidden');
            pollImportStatus(data.import.id);
        } else {
            alert('Import failed: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Start Import';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to start import');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Start Import';
    }
});

// Poll import status
function pollImportStatus(importId) {
    const interval = setInterval(async () => {
        try {
            const response = await fetch(`/admin/products/imports/${importId}/status`);
            const status = await response.json();
            
            updateProgress(status);
            
            if (status.status === 'completed' || status.status === 'failed' || status.status === 'cancelled') {
                clearInterval(interval);
            }
        } catch (error) {
            console.error('Error polling status:', error);
        }
    }, 2000);
}

// Update progress display
function updateProgress(status) {
    document.getElementById('progress-bar').style.width = status.progress + '%';
    document.getElementById('progress-text').textContent = status.progress.toFixed(1) + '%';
    document.getElementById('processed-rows').textContent = status.processed_rows;
    document.getElementById('total-rows').textContent = status.total_rows;
    document.getElementById('successful-rows').textContent = status.successful_rows;
    document.getElementById('failed-rows').textContent = status.failed_rows;
}

// Handle export form
document.getElementById('export-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const formAction = this.action || '{{ route('admin.products.export') }}';
    
    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = formAction;
    
    Array.from(formData.entries()).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });
    
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
});

// Show errors
async function showErrors(importId) {
    try {
        const response = await fetch(`/admin/products/imports/${importId}/errors`);
        const data = await response.json();
        
        const content = document.getElementById('errors-content');
        content.innerHTML = '<table class="min-w-full"><thead><tr><th class="text-left">Row</th><th class="text-left">Error</th></tr></thead><tbody>';
        
        data.errors.forEach(error => {
            content.innerHTML += `<tr><td class="px-2 py-1">${error.row_number}</td><td class="px-2 py-1">${error.error_message}</td></tr>`;
        });
        
        content.innerHTML += '</tbody></table>';
        
        document.getElementById('errors-modal').classList.remove('hidden');
    } catch (error) {
        console.error('Error loading errors:', error);
    }
}

// Close errors modal
function closeErrorsModal() {
    document.getElementById('errors-modal').classList.add('hidden');
}

// Cancel import
async function cancelImport(importId) {
    if (!confirm('Are you sure you want to cancel this import?')) return;
    
    try {
        const response = await fetch(`/admin/products/imports/${importId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to cancel import');
        }
    } catch (error) {
        console.error('Error cancelling import:', error);
    }
}
</script>
@endpush

@endsection

