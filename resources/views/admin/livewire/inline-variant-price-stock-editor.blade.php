<div>
    <div class="mb-4">
        <h3 class="text-lg font-semibold">Inline Variant Editor</h3>
        <p class="text-sm text-gray-600">Click on price or stock to edit inline</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Options</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enabled</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($variants as $variant)
                    <tr>
                        <td class="px-4 py-3 text-sm">{{ $variant['sku'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $variant['name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $variant['options'] }}</td>
                        
                        <td class="px-4 py-3 text-sm">
                            @if(isset($editing["{$variant['id']}_stock"]))
                                <input 
                                    type="number" 
                                    wire:model="variants.{{ array_search($variant['id'], array_column($variants, 'id')) }}.stock"
                                    wire:blur="saveField({{ $variant['id'] }}, 'stock')"
                                    class="w-20 px-2 py-1 border rounded"
                                    autofocus
                                />
                            @else
                                <span 
                                    wire:click="startEditing({{ $variant['id'] }}, 'stock')"
                                    class="cursor-pointer hover:bg-gray-100 px-2 py-1 rounded"
                                >
                                    {{ $variant['stock'] }}
                                </span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-3 text-sm">
                            @if(isset($editing["{$variant['id']}_price"]))
                                <div class="flex items-center space-x-1">
                                    <span class="text-gray-500">{{ $variant['currency'] }}</span>
                                    <input 
                                        type="number" 
                                        step="0.01"
                                        wire:model="variants.{{ array_search($variant['id'], array_column($variants, 'id')) }}.price"
                                        wire:blur="saveField({{ $variant['id'] }}, 'price')"
                                        class="w-24 px-2 py-1 border rounded"
                                        autofocus
                                    />
                                </div>
                            @else
                                <span 
                                    wire:click="startEditing({{ $variant['id'] }}, 'price')"
                                    class="cursor-pointer hover:bg-gray-100 px-2 py-1 rounded"
                                >
                                    {{ $variant['currency'] }} {{ number_format($variant['price'], 2) }}
                                </span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-3 text-sm">
                            <input 
                                type="checkbox" 
                                wire:model.live="variants.{{ array_search($variant['id'], array_column($variants, 'id')) }}.enabled"
                                wire:change="saveField({{ $variant['id'] }}, 'enabled')"
                                class="rounded"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


