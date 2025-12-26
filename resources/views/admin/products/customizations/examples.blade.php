@extends('admin.layout')

@section('title', 'Customization Examples')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Examples for {{ $product->translateAttribute('name') }}</h2>
        <p class="text-sm text-slate-600">Show shoppers sample personalization results.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Add example</h3>
        <form id="example-form" method="POST" action="{{ route('admin.products.customizations.examples.store', $product) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-slate-600 mb-1">Title</label>
                <input type="text" name="title" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Customization ID (optional)</label>
                <input type="number" name="customization_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-slate-600 mb-1">Description</label>
                <input type="text" name="description" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Display order</label>
                <input type="number" name="display_order" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" min="0">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Example image</label>
                <input type="file" name="example_image" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div class="md:col-span-2 flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Add example</button>
                <span id="example-message" class="text-sm text-slate-600"></span>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Title</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Order</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($examples as $example)
                    <tr>
                        <td class="px-4 py-3">{{ $example->title }}</td>
                        <td class="px-4 py-3">{{ $example->description }}</td>
                        <td class="px-4 py-3">{{ $example->display_order }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-slate-500">No examples yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
const exampleForm = document.getElementById('example-form');
const exampleMessage = document.getElementById('example-message');

exampleForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    exampleMessage.textContent = 'Uploading...';

    const formData = new FormData(exampleForm);

    try {
        const response = await fetch(exampleForm.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();
        exampleMessage.textContent = data.message || 'Example saved.';
        if (response.ok) {
            setTimeout(() => window.location.reload(), 800);
        }
    } catch (error) {
        exampleMessage.textContent = 'Failed to save example.';
    }
});
</script>
@endpush
@endsection
