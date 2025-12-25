@extends('adminhub::layouts.app')

@section('main')
<div class="space-y-4">
    <header class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Smart Collection Rules: {{ $collection->translateAttribute('name') }}</h1>
        <div class="flex gap-2">
            <button onclick="previewRules()" class="btn btn-secondary">
                Preview Results
            </button>
            <form action="{{ route('admin.collections.smart-rules.process', $collection) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    Process Collection Now
                </button>
            </form>
        </div>
    </header>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Rule Builder -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Add New Rule</h2>
        
        <form id="rule-form" action="{{ route('admin.collections.smart-rules.store', $collection) }}" method="POST">
            @csrf
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 sm:col-span-3">
                    <label for="field" class="block text-sm font-medium text-gray-700">Field</label>
                    <select id="field" name="field" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        <option value="">Select Field</option>
                        @foreach($availableFields as $fieldKey => $fieldConfig)
                            <option value="{{ $fieldKey }}">{{ $fieldConfig['label'] }}</option>
                        @endforeach
                    </select>
                    @error('field') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="col-span-12 sm:col-span-3">
                    <label for="operator" class="block text-sm font-medium text-gray-700">Operator</label>
                    <select id="operator" name="operator" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        <option value="">Select Operator</option>
                    </select>
                    @error('operator') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="col-span-12 sm:col-span-4" id="value-container">
                    <label for="value" class="block text-sm font-medium text-gray-700">Value</label>
                    <input type="text" id="value" name="value" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="col-span-12 sm:col-span-2">
                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                    <input type="number" id="priority" name="priority" value="0" min="0" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 mt-4">
                <div class="col-span-12 sm:col-span-4">
                    <label for="logic_group" class="block text-sm font-medium text-gray-700">Logic Group (Optional)</label>
                    <input type="text" id="logic_group" name="logic_group" placeholder="e.g., group1" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Group rules together with AND/OR logic</p>
                </div>

                <div class="col-span-12 sm:col-span-3">
                    <label for="group_operator" class="block text-sm font-medium text-gray-700">Group Operator</label>
                    <select id="group_operator" name="group_operator" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="and">AND</option>
                        <option value="or">OR</option>
                    </select>
                </div>

                <div class="col-span-12 sm:col-span-5">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                    <input type="text" id="description" name="description" placeholder="Rule description" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked class="mr-2">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Add Rule</button>
            </div>
        </form>
    </div>

    <!-- Existing Rules -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold">Current Rules ({{ $rules->count() }})</h2>
        </div>

        @if($rules->isEmpty())
            <div class="p-6 text-center text-gray-500">
                No rules defined. Add a rule above to get started.
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($rules as $rule)
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($rule->logic_group)
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                            Group: {{ $rule->logic_group }} ({{ strtoupper($rule->group_operator) }})
                                        </span>
                                    @endif
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">
                                        Priority: {{ $rule->priority }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $availableFields[$rule->field]['label'] ?? $rule->field }}
                                    {{ $availableOperators[$rule->operator] ?? $rule->operator }}
                                    @if($rule->value)
                                        <span class="text-gray-600">
                                            {{ is_array($rule->value) ? json_encode($rule->value) : $rule->value }}
                                        </span>
                                    @endif
                                </p>
                                @if($rule->description)
                                    <p class="text-sm text-gray-500 mt-1">{{ $rule->description }}</p>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.collections.smart-rules.edit', [$collection, $rule]) }}" class="btn btn-sm btn-secondary">Edit</a>
                                <form action="{{ route('admin.collections.smart-rules.destroy', [$collection, $rule]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this rule?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Preview Results</h3>
            <div id="preview-content" class="text-sm text-gray-600">
                Loading...
            </div>
            <div class="mt-4">
                <button onclick="closePreview()" class="btn btn-secondary w-full">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const availableFields = @json($availableFields);
const availableOperators = @json($availableOperators);
const productTypes = @json($productTypes);
const brands = @json($brands);
const categories = @json($categories);
const attributes = @json($attributes);

// Update operators when field changes
document.getElementById('field').addEventListener('change', function() {
    const field = this.value;
    const operatorSelect = document.getElementById('operator');
    const valueContainer = document.getElementById('value-container');
    
    operatorSelect.innerHTML = '<option value="">Select Operator</option>';
    
    if (field && availableFields[field]) {
        const fieldConfig = availableFields[field];
        fieldConfig.operators.forEach(op => {
            const option = document.createElement('option');
            option.value = op;
            option.textContent = availableOperators[op] || op;
            operatorSelect.appendChild(option);
        });
        
        // Update value input based on field type
        updateValueInput(field, fieldConfig.value_type);
    }
});

function updateValueInput(field, valueType) {
    const container = document.getElementById('value-container');
    const label = container.querySelector('label');
    let input = document.getElementById('value');
    
    if (!input) {
        input = document.createElement('input');
        input.type = 'text';
        input.id = 'value';
        input.name = 'value';
        input.className = 'mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm';
        container.appendChild(input);
    }
    
    // Clear existing value
    input.value = '';
    input.type = 'text';
    input.multiple = false;
    input.innerHTML = '';
    
    switch(valueType) {
        case 'number':
            input.type = 'number';
            input.step = field === 'rating' ? '0.1' : '0.01';
            input.min = field === 'rating' ? '0' : '0';
            input.max = field === 'rating' ? '5' : '';
            if (availableOperators[document.getElementById('operator').value]?.includes('between')) {
                input.placeholder = 'Min, Max (e.g., 10, 100)';
            }
            break;
        case 'date':
            input.type = 'date';
            break;
        case 'select':
            // Replace with select dropdown
            const select = document.createElement('select');
            select.id = 'value';
            select.name = 'value';
            select.className = input.className;
            select.multiple = ['in', 'not_in'].includes(document.getElementById('operator').value);
            
            let options = [];
            if (field === 'product_type') {
                options = productTypes.map(t => ({value: t.id, label: t.name}));
            } else if (field === 'brand') {
                options = brands.map(b => ({value: b.id, label: b.name}));
            } else if (field === 'category') {
                options = categories.map(c => ({value: c.id, label: c.translateAttribute('name')}));
            } else if (field === 'inventory_status') {
                options = [
                    {value: 'in_stock', label: 'In Stock'},
                    {value: 'out_of_stock', label: 'Out of Stock'},
                    {value: 'low_stock', label: 'Low Stock'},
                    {value: 'backorder', label: 'Backorder'},
                ];
            }
            
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                select.appendChild(option);
            });
            
            container.replaceChild(select, input);
            input = select;
            break;
        case 'attribute':
            // Complex attribute selector
            const attrSelect = document.createElement('select');
            attrSelect.id = 'attribute-select';
            attrSelect.name = 'attribute_select';
            attrSelect.className = input.className;
            attrSelect.innerHTML = '<option value="">Select Attribute</option>';
            attributes.forEach(attr => {
                const option = document.createElement('option');
                option.value = attr.handle;
                option.textContent = attr.translateAttribute('name') || attr.name;
                attrSelect.appendChild(option);
            });
            
            const valueSelect = document.createElement('select');
            valueSelect.id = 'value';
            valueSelect.name = 'value';
            valueSelect.className = input.className + ' mt-2';
            valueSelect.multiple = true;
            valueSelect.innerHTML = '<option value="">Select Attribute Values</option>';
            
            attrSelect.addEventListener('change', function() {
                const attr = attributes.find(a => a.handle === this.value);
                valueSelect.innerHTML = '<option value="">Select Attribute Values</option>';
                if (attr && attr.values) {
                    attr.values.forEach(val => {
                        const option = document.createElement('option');
                        option.value = val.id;
                        option.textContent = val.translateAttribute('name') || val.name;
                        valueSelect.appendChild(option);
                    });
                }
            });
            
            container.innerHTML = '';
            container.appendChild(label);
            container.appendChild(attrSelect);
            container.appendChild(valueSelect);
            break;
        default:
            input.placeholder = 'Enter value';
    }
}

// Update value input when operator changes
document.getElementById('operator').addEventListener('change', function() {
    const field = document.getElementById('field').value;
    if (field && availableFields[field]) {
        updateValueInput(field, availableFields[field].value_type);
    }
});

function previewRules() {
    const modal = document.getElementById('preview-modal');
    const content = document.getElementById('preview-content');
    
    modal.classList.remove('hidden');
    content.textContent = 'Loading...';
    
    fetch('{{ route('admin.collections.smart-rules.preview', $collection) }}')
        .then(response => response.json())
        .then(data => {
            let html = `<p class="font-semibold mb-2">Found ${data.count} products</p>`;
            if (data.products.length > 0) {
                html += '<ul class="list-disc list-inside space-y-1">';
                data.products.forEach(product => {
                    html += `<li>${product.name} (SKU: ${product.sku})</li>`;
                });
                html += '</ul>';
                if (data.count > 10) {
                    html += `<p class="mt-2 text-gray-500">... and ${data.count - 10} more</p>`;
                }
            }
            content.innerHTML = html;
        })
        .catch(error => {
            content.textContent = 'Error loading preview: ' + error.message;
        });
}

function closePreview() {
    document.getElementById('preview-modal').classList.add('hidden');
}

// Handle form submission for complex values
document.getElementById('rule-form').addEventListener('submit', function(e) {
    const field = document.getElementById('field').value;
    const valueInput = document.getElementById('value');
    
    if (field === 'attribute' && document.getElementById('attribute-select')) {
        const attrSelect = document.getElementById('attribute-select');
        const values = Array.from(valueInput.selectedOptions).map(opt => opt.value);
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'value';
        hiddenInput.value = JSON.stringify({
            attribute_handle: attrSelect.value,
            values: values
        });
        this.appendChild(hiddenInput);
        valueInput.disabled = true;
    }
});
</script>
@endpush
@endsection

