<div>
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Product Variants</h3>
        <div class="flex gap-2">
            <button 
                wire:click="$set('showGenerateForm', true)"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                Generate Variants
            </button>
            @if(count($selectedVariants) > 0)
                <button 
                    wire:click="$set('showBulkEditForm', true)"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                >
                    Bulk Edit ({{ count($selectedVariants) }})
                </button>
            @endif
        </div>
    </div>

    @if($showGenerateForm)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <form wire:submit.prevent="generateVariants">
                {{ $this->generateForm }}
                <div class="flex gap-2 mt-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Generate
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('showGenerateForm', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if($showBulkEditForm)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <form wire:submit.prevent="bulkUpdate">
                {{ $this->bulkEditForm }}
                <div class="flex gap-2 mt-4">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Update Selected
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('showBulkEditForm', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <input 
                            type="checkbox" 
                            wire:model="selectAll"
                            class="rounded"
                        >
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Options</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($variants as $variant)
                    <tr>
                        <td class="px-4 py-3">
                            <input 
                                type="checkbox" 
                                wire:model="selectedVariants"
                                value="{{ $variant['id'] }}"
                                class="rounded"
                            >
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $variant['sku'] ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if(isset($variant['variant_options']))
                                @foreach($variant['variant_options'] as $option)
                                    <span class="inline-block px-2 py-1 text-xs bg-gray-100 rounded">
                                        {{ $option['option']['name'] ?? '' }}: {{ $option['name'] ?? '' }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-400">No options</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $variant['stock'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if(isset($variant['prices']) && count($variant['prices']) > 0)
                                ${{ number_format($variant['prices'][0]['price'] / 100, 2) }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($variant['enabled'] ?? true)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Enabled</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Disabled</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <button 
                                wire:click="deleteVariant({{ $variant['id'] }})"
                                wire:confirm="Are you sure you want to delete this variant?"
                                class="text-red-600 hover:text-red-800"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No variants found. Generate variants to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

