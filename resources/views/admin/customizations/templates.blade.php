@extends('admin.layout')

@section('title', 'Customization Templates')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Customization templates</h2>
        <p class="text-sm text-slate-600">Reusable field sets for product personalization.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Create template</h3>
        <form id="template-form" method="POST" action="{{ route('admin.customizations.templates.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-slate-600 mb-1">Name</label>
                <input type="text" name="name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Category</label>
                <input type="text" name="category" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-slate-600 mb-1">Description</label>
                <input type="text" name="description" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Field label</label>
                <input type="text" name="template_data[0][label]" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Field type</label>
                <input type="text" name="template_data[0][type]" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-slate-600 mb-1">Preview image</label>
                <input type="file" name="preview_image" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2 flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Save template</button>
                <span id="template-message" class="text-sm text-slate-600"></span>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($templates as $template)
                    <tr>
                        <td class="px-4 py-3">{{ $template->name }}</td>
                        <td class="px-4 py-3">{{ $template->category }}</td>
                        <td class="px-4 py-3">{{ $template->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-slate-500">No templates created.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $templates->links() }}
    </div>
</div>

@push('scripts')
<script>
const templateForm = document.getElementById('template-form');
const templateMessage = document.getElementById('template-message');

templateForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    templateMessage.textContent = 'Saving...';

    const formData = new FormData(templateForm);

    try {
        const response = await fetch(templateForm.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();
        templateMessage.textContent = data.message || 'Template saved.';
        if (response.ok) {
            setTimeout(() => window.location.reload(), 800);
        }
    } catch (error) {
        templateMessage.textContent = 'Failed to save template.';
    }
});
</script>
@endpush
@endsection
