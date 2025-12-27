@extends('admin.layout')

@section('title', 'Recommendation Rules')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-semibold">Recommendation rules</h2>
            <p class="text-sm text-slate-600">Curate product recommendations and upsell logic.</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Create rule</h3>
            <form id="recommendation-form" method="POST" action="{{ url()->current() }}"
                  class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Source product ID</label>
                    <input type="number" name="source_product_id"
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Recommended product ID</label>
                    <input type="number" name="recommended_product_id"
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Rule type</label>
                    <select name="rule_type" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                        <option value="manual">Manual</option>
                        <option value="category">Category</option>
                        <option value="attribute">Attribute</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs text-slate-600 mb-1">Name</label>
                    <input type="text" name="name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Priority</label>
                    <input type="number" name="priority" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                           min="0" max="100" value="0">
                </div>
                <div class="md:col-span-3 flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Save rule</button>
                    <span id="recommendation-message" class="text-sm text-slate-600"></span>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Rule</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Source</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Recommended</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Priority</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rules as $rule)
                        <tr>
                            <td class="px-4 py-3">{{ $rule->name ?? 'Rule #' . $rule->id }}</td>
                            <td class="px-4 py-3">{{ $rule->sourceProduct?->translateAttribute('name') ?? 'Any' }}</td>
                            <td class="px-4 py-3">{{ $rule->recommendedProduct?->translateAttribute('name') ?? 'Product' }}
                            </td>
                            <td class="px-4 py-3">{{ $rule->priority }}</td>
                            <td class="px-4 py-3">{{ $rule->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">No recommendation rules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $rules->links() }}
        </div>
    </div>

    @push('scripts')
    @endpush
@endsection
