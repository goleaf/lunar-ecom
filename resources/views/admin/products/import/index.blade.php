@extends('admin.layout')

@section('title', 'Product Import/Export')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Product Import/Export</h1>
            <div class="flex gap-2">
                <a href="{{ route('admin.products.import.template.download') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Download Template
                </a>
                <button onclick="showExportModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Export Products
                </button>
            </div>
        </div>

        <!-- Import Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Import Products</h2>
            
            <!-- Drag and Drop Upload Area -->
            <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-500 transition-colors">
                <input type="file" id="fileInput" accept=".csv,.xlsx,.xls" class="hidden">
                <div class="space-y-4">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div>
                        <p class="text-lg font-medium text-gray-700">Drag and drop your file here</p>
                        <p class="text-sm text-gray-500 mt-1">or</p>
                        <button onclick="document.getElementById('fileInput').click()" class="mt-2 text-indigo-600 hover:text-indigo-800 font-medium">
                            Browse Files
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">Supports CSV, XLSX, XLS (Max 10MB)</p>
                </div>
            </div>

            <!-- Import Options -->
            <div id="importOptions" class="hidden mt-6 space-y-4">
                <h3 class="text-lg font-semibold">Import Options</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                    <select id="importAction" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="create">Create New Products Only</option>
                        <option value="update">Update Existing Products Only</option>
                        <option value="create_or_update" selected>Create or Update</option>
                    </select>
                </div>

                <!-- Field Mapping -->
                <div id="fieldMapping" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Field Mapping</label>
                    <p class="text-xs text-gray-500">Map your file columns to product fields</p>
                    <!-- Field mapping will be populated dynamically -->
                </div>

                <!-- Preview Section -->
                <div id="previewSection" class="hidden mt-6">
                    <h3 class="text-lg font-semibold mb-4">Preview (First 10 Rows)</h3>
                    <div id="previewTable" class="overflow-x-auto border rounded-lg"></div>
                    <div id="validationErrors" class="mt-4"></div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button onclick="startImport()" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                        Start Import
                    </button>
                    <button onclick="resetImport()" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Import History -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Import History</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Filename</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $import->original_filename }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($import->status === 'completed') bg-green-100 text-green-800
                                        @elseif($import->status === 'failed') bg-red-100 text-red-800
                                        @elseif($import->status === 'processing') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($import->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($import->status === 'processing')
                                        <div class="flex items-center">
                                            <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $import->getProgressPercentage() }}%"></div>
                                            </div>
                                            <span>{{ $import->getProgressPercentage() }}%</span>
                                        </div>
                                    @else
                                        {{ $import->processed_rows }} / {{ $import->total_rows }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="text-green-600">{{ $import->successful_rows }} success</span>
                                    @if($import->failed_rows > 0)
                                        <span class="text-red-600 ml-2">{{ $import->failed_rows }} failed</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $import->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.products.import.report', $import->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View Report</a>
                                    @if($import->canRollback())
                                        <button onclick="rollbackImport({{ $import->id }})" class="text-red-600 hover:text-red-900">Rollback</button>
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
</div>

<!-- Export Modal -->
<div id="exportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium mb-4">Export Products</h3>
        <form id="exportForm" method="POST" action="{{ route('admin.products.export.export') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                    <select name="format" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="xlsx">Excel (XLSX)</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Categories</option>
                        <!-- Populate with categories -->
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                    <select name="brand_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Brands</option>
                        <!-- Populate with brands -->
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock Status</label>
                    <select name="stock_status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All</option>
                        <option value="in_stock">In Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                        <option value="low_stock">Low Stock</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Export
                </button>
                <button type="button" onclick="closeExportModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentFile = null;
let previewData = null;

// File upload handling
document.getElementById('fileInput').addEventListener('change', handleFileSelect);
document.getElementById('dropZone').addEventListener('dragover', (e) => {
    e.preventDefault();
    e.currentTarget.classList.add('border-indigo-500');
});
document.getElementById('dropZone').addEventListener('dragleave', (e) => {
    e.currentTarget.classList.remove('border-indigo-500');
});
document.getElementById('dropZone').addEventListener('drop', (e) => {
    e.preventDefault();
    e.currentTarget.classList.remove('border-indigo-500');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

function handleFileSelect(e) {
    if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
    }
}

function handleFile(file) {
    currentFile = file;
    previewFile(file);
}

function previewFile(file) {
    const formData = new FormData();
    formData.append('file', file);

    fetch('{{ route("admin.products.import.preview") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            previewData = data;
            displayPreview(data);
            document.getElementById('importOptions').classList.remove('hidden');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to preview file');
    });
}

function displayPreview(data) {
    // Build field mapping
    buildFieldMapping(data.headers);
    
    // Display preview table
    let tableHtml = '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    data.headers.forEach(header => {
        tableHtml += `<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">${header}</th>`;
    });
    tableHtml += '</tr></thead><tbody>';
    
    data.preview_rows.forEach((row, index) => {
        const rowNumber = index + 2;
        const hasErrors = data.validation_errors && data.validation_errors[rowNumber];
        tableHtml += `<tr class="${hasErrors ? 'bg-red-50' : ''}">`;
        row.forEach(cell => {
            tableHtml += `<td class="px-4 py-2 text-sm text-gray-900">${cell || ''}</td>`;
        });
        tableHtml += '</tr>';
    });
    
    tableHtml += '</tbody></table>';
    document.getElementById('previewTable').innerHTML = tableHtml;
    
    // Display validation errors
    if (data.validation_errors && Object.keys(data.validation_errors).length > 0) {
        let errorsHtml = '<div class="bg-red-50 border border-red-200 rounded p-4"><h4 class="font-semibold text-red-800 mb-2">Validation Errors:</h4><ul class="list-disc list-inside text-sm text-red-700">';
        Object.entries(data.validation_errors).forEach(([row, errors]) => {
            errorsHtml += `<li>Row ${row}: ${Object.values(errors).join(', ')}</li>`;
        });
        errorsHtml += '</ul></div>';
        document.getElementById('validationErrors').innerHTML = errorsHtml;
    } else {
        document.getElementById('validationErrors').innerHTML = '';
    }
    
    document.getElementById('previewSection').classList.remove('hidden');
}

function buildFieldMapping(headers) {
    const defaultMapping = {
        'SKU': 'sku',
        'Name': 'name',
        'Description': 'description',
        'Price': 'price',
        'Compare At Price': 'compare_at_price',
        'Category Path': 'category_path',
        'Brand': 'brand',
        'Images (URLs)': 'images',
        'Attributes (JSON)': 'attributes',
        'Stock Quantity': 'stock_quantity',
        'Weight (grams)': 'weight',
        'Length (cm)': 'length',
        'Width (cm)': 'width',
        'Height (cm)': 'height',
    };
    
    let mappingHtml = '';
    headers.forEach(header => {
        const mappedField = defaultMapping[header] || '';
        mappingHtml += `
            <div class="flex items-center gap-2">
                <label class="w-32 text-sm text-gray-700">${header}:</label>
                <select name="field_mapping[${header}]" class="flex-1 border border-gray-300 rounded-md px-3 py-1 text-sm">
                    <option value="">-- Skip --</option>
                    <option value="sku" ${mappedField === 'sku' ? 'selected' : ''}>SKU</option>
                    <option value="name" ${mappedField === 'name' ? 'selected' : ''}>Name</option>
                    <option value="description" ${mappedField === 'description' ? 'selected' : ''}>Description</option>
                    <option value="price" ${mappedField === 'price' ? 'selected' : ''}>Price</option>
                    <option value="compare_at_price" ${mappedField === 'compare_at_price' ? 'selected' : ''}>Compare At Price</option>
                    <option value="category_path" ${mappedField === 'category_path' ? 'selected' : ''}>Category Path</option>
                    <option value="brand" ${mappedField === 'brand' ? 'selected' : ''}>Brand</option>
                    <option value="images" ${mappedField === 'images' ? 'selected' : ''}>Images</option>
                    <option value="attributes" ${mappedField === 'attributes' ? 'selected' : ''}>Attributes</option>
                    <option value="stock_quantity" ${mappedField === 'stock_quantity' ? 'selected' : ''}>Stock Quantity</option>
                    <option value="weight" ${mappedField === 'weight' ? 'selected' : ''}>Weight</option>
                    <option value="length" ${mappedField === 'length' ? 'selected' : ''}>Length</option>
                    <option value="width" ${mappedField === 'width' ? 'selected' : ''}>Width</option>
                    <option value="height" ${mappedField === 'height' ? 'selected' : ''}>Height</option>
                </select>
            </div>
        `;
    });
    
    document.getElementById('fieldMapping').innerHTML = mappingHtml;
}

function startImport() {
    if (!previewData) {
        alert('Please upload and preview a file first');
        return;
    }
    
    const formData = new FormData();
    formData.append('file_path', previewData.file_path);
    formData.append('filename', previewData.filename);
    formData.append('file_type', previewData.file_type);
    formData.append('import_options[action]', document.getElementById('importAction').value);
    
    // Collect field mapping
    const fieldMapping = {};
    document.querySelectorAll('[name^="field_mapping"]').forEach(select => {
        const header = select.name.match(/\[(.*?)\]/)[1];
        if (select.value) {
            fieldMapping[select.value] = header;
        }
    });
    formData.append('field_mapping', JSON.stringify(fieldMapping));
    
    fetch('{{ route("admin.products.import.import") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Import started successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to start import');
    });
}

function resetImport() {
    currentFile = null;
    previewData = null;
    document.getElementById('fileInput').value = '';
    document.getElementById('importOptions').classList.add('hidden');
    document.getElementById('previewSection').classList.add('hidden');
}

function showExportModal() {
    document.getElementById('exportModal').classList.remove('hidden');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

function rollbackImport(importId) {
    if (!confirm('Are you sure you want to rollback this import? This action cannot be undone.')) {
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
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Poll for import status updates
setInterval(() => {
    document.querySelectorAll('[data-import-id]').forEach(element => {
        const importId = element.getAttribute('data-import-id');
        fetch(`/admin/products/import/${importId}/status`)
            .then(response => response.json())
            .then(data => {
                // Update progress bars and status
                // Implementation depends on your UI structure
            });
    });
}, 5000); // Poll every 5 seconds
</script>
@endsection

