<div>
    <div class="mb-4">
        <h3 class="text-lg font-semibold">Variant Matrix Editor</h3>
        <p class="text-sm text-gray-600">
            {{ $rowAttribute['name'] ?? '' }} (Rows) × {{ $columnAttribute['name'] ?? '' }} (Columns)
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse border border-gray-300">
            <thead>
                <tr>
                    <th class="border border-gray-300 p-2 bg-gray-100"></th>
                    @foreach($columnAttribute['values'] ?? [] as $colValue)
                        <th class="border border-gray-300 p-2 bg-gray-100 text-center">
                            {{ $colValue['name'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rowAttribute['values'] ?? [] as $rowValue)
                    <tr>
                        <td class="border border-gray-300 p-2 bg-gray-100 font-semibold">
                            {{ $rowValue['name'] }}
                        </td>
                        @foreach($columnAttribute['values'] ?? [] as $colValue)
                            @php
                                $key = "{$rowValue['id']}_{$colValue['id']}";
                                $cell = $cellData[$key] ?? null;
                            @endphp
                            <td class="border border-gray-300 p-2">
                                @if($cell)
                                    <div class="space-y-2">
                                        <div class="text-xs text-gray-500">
                                            SKU: {{ $cell['sku'] ?: 'N/A' }}
                                        </div>
                                        
                                        <input 
                                            type="number" 
                                            wire:model.live="cellData.{{ $key }}.stock"
                                            wire:change="saveCell('{{ $key }}')"
                                            placeholder="Stock"
                                            class="w-full px-2 py-1 text-sm border rounded"
                                        />
                                        
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:model.live="cellData.{{ $key }}.price"
                                            wire:change="saveCell('{{ $key }}')"
                                            placeholder="Price"
                                            class="w-full px-2 py-1 text-sm border rounded"
                                        />
                                        
                                        <label class="flex items-center space-x-2">
                                            <input 
                                                type="checkbox" 
                                                wire:model.live="cellData.{{ $key }}.enabled"
                                                wire:change="saveCell('{{ $key }}')"
                                                class="rounded"
                                            />
                                            <span class="text-xs">Enabled</span>
                                        </label>
                                        
                                        @if($cell['exists'])
                                            <button 
                                                wire:click="deleteCell('{{ $key }}')"
                                                class="text-xs text-red-600 hover:text-red-800"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


