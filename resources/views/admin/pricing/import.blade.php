@extends('admin.layout')

@section('title', 'Pricing Import')

@section('content')
@php
    $productRoute = request()->route('product');
    $productId = is_object($productRoute) ? $productRoute->id : $productRoute;
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Import pricing data</h2>
        <p class="text-sm text-slate-600 mb-6">Upload a CSV or XLSX file to update pricing matrices for this product.</p>

        <form id="pricing-import-form" method="POST" action="{{ route('admin.products.pricing.import', ['product' => $productId]) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Import file</label>
                <input type="file" name="file" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Import type</label>
                <select name="import_type" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                    <option value="quantity_tiers">Quantity tiers</option>
                    <option value="customer_group">Customer group pricing</option>
                    <option value="regional">Regional pricing</option>
                    <option value="bulk">Bulk update</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Import pricing</button>
                <span id="import-status" class="text-sm text-slate-600"></span>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const importForm = document.getElementById('pricing-import-form');
const importStatus = document.getElementById('import-status');

importForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    importStatus.textContent = 'Uploading...';

    const formData = new FormData(importForm);

    try {
        const response = await fetch(importForm.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();
        importStatus.textContent = data.message || 'Import complete.';
    } catch (error) {
        importStatus.textContent = 'Import failed.';
    }
});
</script>
@endpush
@endsection
