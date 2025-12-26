<div class="bg-white rounded-lg shadow p-6 space-y-4">
    <div>
        <h3 class="text-lg font-semibold">Bulk attribute editor</h3>
        <p class="text-sm text-slate-600">Update attributes for {{ count($selectedProducts) }} selected products.</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($attributes as $attributeId => $attributeName)
                <label class="block text-sm">
                    <span class="text-slate-700">{{ $attributeName }}</span>
                    <input type="text" wire:model.defer="values.{{ $attributeId }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </label>
            @empty
                <p class="text-sm text-slate-500">No attributes available.</p>
            @endforelse
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Apply updates</button>
    </form>
</div>
