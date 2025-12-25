@extends('admin.layout')

@section('title', 'Stock Details - ' . $variant->product->translateAttribute('name'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold mb-2">{{ $variant->product->translateAttribute('name') }}</h1>
        <p class="text-gray-600">SKU: {{ $variant->sku }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Inventory Levels --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Inventory Levels by Warehouse</h2>
                <div class="space-y-4">
                    @foreach($inventoryLevels as $level)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-semibold">{{ $level->warehouse->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $level->warehouse->full_address }}</p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    {{ $level->status === 'in_stock' ? 'bg-green-100 text-green-800' : 
                                       ($level->status === 'low_stock' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $level->status)) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-4 mt-4">
                                <div>
                                    <p class="text-sm text-gray-600">Available</p>
                                    <p class="text-lg font-semibold">{{ $level->available_quantity }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Reserved</p>
                                    <p class="text-lg font-semibold">{{ $level->reserved_quantity }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total</p>
                                    <p class="text-lg font-semibold">{{ $level->quantity }}</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t">
                                <p class="text-sm text-gray-600">Reorder Point: <span class="font-medium">{{ $level->reorder_point }}</span></p>
                                <p class="text-sm text-gray-600">Reorder Quantity: <span class="font-medium">{{ $level->reorder_quantity }}</span></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Stock Movements --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Stock Movements</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warehouse</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Before</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">After</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($stockMovements as $movement)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        {{ $movement->movement_date->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100">
                                            {{ ucfirst($movement->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ $movement->warehouse?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium 
                                        {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ $movement->quantity_before }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ $movement->quantity_after }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $movement->reason }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                        No stock movements found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $stockMovements->links() }}
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Active Reservations --}}
            @if($reservations->count() > 0)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Active Reservations</h2>
                    <div class="space-y-3">
                        @foreach($reservations as $reservation)
                            <div class="border rounded p-3">
                                <p class="text-sm font-medium">{{ $reservation->warehouse->name }}</p>
                                <p class="text-sm text-gray-600">Quantity: {{ $reservation->quantity }}</p>
                                <p class="text-xs text-gray-500">Expires: {{ $reservation->expires_at->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <button onclick="openAdjustModal()" class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Adjust Stock
                    </button>
                    <button onclick="openTransferModal()" class="w-full px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        Transfer Stock
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Adjust Stock Modal --}}
<div id="adjustModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Adjust Stock</h3>
        <form id="adjustForm" method="POST" action="{{ route('admin.stock.adjust', $variant) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Warehouse</label>
                    <select name="warehouse_id" required class="w-full border rounded px-3 py-2">
                        @foreach(\App\Models\Warehouse::active()->get() as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Quantity</label>
                    <input type="number" name="quantity" required class="w-full border rounded px-3 py-2" 
                           placeholder="Positive to add, negative to subtract">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Reason</label>
                    <input type="text" name="reason" class="w-full border rounded px-3 py-2" 
                           placeholder="Reason for adjustment">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Notes</label>
                    <textarea name="notes" class="w-full border rounded px-3 py-2" rows="3"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeAdjustModal()" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Adjust
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjustModal() {
    document.getElementById('adjustModal').classList.remove('hidden');
}

function closeAdjustModal() {
    document.getElementById('adjustModal').classList.add('hidden');
}

function openTransferModal() {
    alert('Transfer modal - implement as needed');
}
</script>
@endsection

