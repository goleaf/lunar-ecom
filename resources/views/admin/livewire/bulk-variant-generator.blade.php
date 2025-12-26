<div class="bg-white rounded-lg shadow p-6 space-y-6">
    <div>
        <h3 class="text-lg font-semibold">Bulk variant generator</h3>
        <p class="text-sm text-slate-600">Generate combinations of option values for {{ $product->translateAttribute('name') }}.</p>
    </div>

    <form wire:submit.prevent="generate" class="space-y-4">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="preview" class="px-4 py-2 text-sm border border-slate-300 rounded">Preview combinations</button>
            <button type="submit" class="px-4 py-2 text-sm bg-slate-900 text-white rounded">Generate variants</button>
        </div>
    </form>

    @if(!empty($previewVariants))
        <div class="bg-slate-50 border border-slate-200 rounded p-4">
            <h4 class="text-sm font-semibold text-slate-700 mb-2">Preview ({{ count($previewVariants) }} combinations)</h4>
            <div class="text-xs text-slate-600">First 10 combinations shown.</div>
            <ul class="mt-3 text-sm text-slate-700 list-disc list-inside space-y-1">
                @foreach(array_slice($previewVariants, 0, 10) as $combo)
                    <li>{{ implode(', ', $combo) }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
