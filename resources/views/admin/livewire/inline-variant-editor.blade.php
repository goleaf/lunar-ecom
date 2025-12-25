<div>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold">Product Variants</h3>
            <button wire:click="$dispatch('open-modal', { id: 'create-variant' })" 
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Add Variant
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Options</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($variants as $variant)
                        <tr>
                            @if($editingVariantId === $variant['id'])
                                <td class="px-4 py-3">
                                    <input type="text" wire:model="editingData.sku" 
                                           class="w-full border-gray-300 rounded">
                                </td>
                                <td class="px-4 py-3">
                                    @foreach($variant['options'] as $option)
                                        <span class="text-sm">{{ $option['option'] }}: {{ $option['value'] }}</span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" wire:model="editingData.price" 
                                           step="0.01" class="w-full border-gray-300 rounded">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" wire:model="editingData.stock" 
                                           class="w-full border-gray-300 rounded">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" wire:model="editingData.enabled">
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="saveVariant" 
                                            class="text-green-600 hover:text-green-800">Save</button>
                                    <button wire:click="cancelEditing" 
                                            class="text-gray-600 hover:text-gray-800 ml-2">Cancel</button>
                                </td>
                            @else
                                <td class="px-4 py-3">{{ $variant['sku'] ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @foreach($variant['options'] as $option)
                                        <span class="text-sm">{{ $option['option'] }}: {{ $option['value'] }}</span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3">${{ number_format($variant['price'], 2) }}</td>
                                <td class="px-4 py-3">{{ $variant['stock'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded {{ $variant['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $variant['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="startEditing({{ $variant['id'] }})" 
                                            class="text-blue-600 hover:text-blue-800">Edit</button>
                                    <button wire:click="deleteVariant({{ $variant['id'] }})" 
                                            class="text-red-600 hover:text-red-800 ml-2">Delete</button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

