@extends('admin.layout')

@section('title', 'Rule Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">{{ $rule->name ?? 'Rule #' . $rule->id }}</h2>
            <p class="text-sm text-slate-600">{{ $rule->description ?: 'No description provided.' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.badges.rules.edit', $rule) }}" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Edit rule</a>
            <form method="POST" action="{{ route('admin.badges.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm border border-red-200 text-red-600 rounded hover:bg-red-50">Delete</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-5 space-y-3">
            <div class="text-xs uppercase text-slate-500">Badge</div>
            <div class="text-sm font-semibold">{{ $rule->badge?->name ?? 'Badge' }}</div>
            <div class="text-xs uppercase text-slate-500 mt-4">Condition type</div>
            <div class="text-sm">{{ ucfirst($rule->condition_type) }}</div>
            <div class="text-xs uppercase text-slate-500 mt-4">Priority</div>
            <div class="text-sm">{{ $rule->priority }}</div>
            <div class="text-xs uppercase text-slate-500 mt-4">Status</div>
            <div class="text-sm">{{ $rule->is_active ? 'Active' : 'Inactive' }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs uppercase text-slate-500">Conditions</div>
            <pre class="mt-3 text-xs bg-slate-50 border border-slate-200 rounded p-3 overflow-x-auto">{{ json_encode($rule->conditions ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold mb-4">Assignments</h3>
        <div class="space-y-3">
            @forelse($rule->assignments as $assignment)
                <div class="flex items-center justify-between border border-slate-200 rounded px-4 py-3">
                    <div>
                        <div class="font-medium">{{ $assignment->product ? $assignment->product->translateAttribute('name') : 'Unknown product' }}</div>
                        <div class="text-xs text-slate-500">Assigned {{ $assignment->assigned_at?->format('M j, Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="text-xs text-slate-500">{{ $assignment->assignment_type ?? 'automatic' }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No assignments yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
