@extends('admin.layout')

@section('title', 'Badge Rules')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Badge rules</h2>
            <p class="text-sm text-slate-600">Automate badge assignment based on product conditions.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.badges.index') }}" class="px-4 py-2 text-sm border border-slate-300 rounded hover:bg-slate-50">Back to badges</a>
            <a href="{{ route('admin.badges.rules.create') }}" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Create rule</a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Rule</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Badge</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Priority</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rules as $rule)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $rule->name ?? 'Rule #' . $rule->id }}</div>
                            <div class="text-xs text-slate-500">{{ $rule->description }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $rule->badge?->name ?? 'Badge' }}</td>
                        <td class="px-4 py-3">{{ ucfirst($rule->condition_type) }}</td>
                        <td class="px-4 py-3">{{ $rule->priority }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $rule->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.badges.rules.show', $rule) }}" class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('admin.badges.rules.edit', $rule) }}" class="ml-3 text-slate-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.badges.rules.destroy', $rule) }}" class="inline" onsubmit="return confirm('Delete this rule?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No rules found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $rules->links() }}
    </div>
</div>
@endsection
