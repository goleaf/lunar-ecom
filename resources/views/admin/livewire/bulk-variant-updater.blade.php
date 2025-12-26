<div class="bg-white rounded-lg shadow p-6 space-y-6">
    <div>
        <h3 class="text-lg font-semibold">Bulk variant updater</h3>
        <p class="text-sm text-slate-600">Update stock, status, and pricing for selected variants.</p>
    </div>

    <form wire:submit.prevent="update" class="space-y-4">
        {{ $this->form }}
        <button type="submit" class="px-4 py-2 text-sm bg-slate-900 text-white rounded">Update variants</button>
    </form>

    <div class="text-sm text-slate-600">Selected variants: {{ count($selectedVariants) }}</div>
</div>
