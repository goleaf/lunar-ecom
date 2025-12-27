@extends('admin.layout')

@section('title', 'Search Synonyms')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Search synonyms</h2>
        <p class="text-sm text-slate-600">Expand search terms with alternate phrases.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Add synonym</h3>
        <form id="synonym-form" class="grid grid-cols-1 md:grid-cols-3 gap-4" data-url="{{ url()->current() }}">
            @csrf
            <div>
                <label class="block text-xs text-slate-600 mb-1">Term</label>
                <input type="text" name="term" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Synonyms (comma separated)</label>
                <input type="text" name="synonyms" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Priority</label>
                <input type="number" name="priority" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" min="0" max="100" value="0">
            </div>
            <div class="md:col-span-3 flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Save synonym</button>
                <span id="synonym-message" class="text-sm text-slate-600"></span>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Term</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Synonyms</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Priority</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($synonyms as $synonym)
                    <tr>
                        <td class="px-4 py-3">{{ $synonym->term }}</td>
                        <td class="px-4 py-3">{{ implode(', ', $synonym->synonyms ?? []) }}</td>
                        <td class="px-4 py-3">{{ $synonym->priority }}</td>
                        <td class="px-4 py-3">{{ $synonym->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No synonyms defined.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
@endpush
@endsection
