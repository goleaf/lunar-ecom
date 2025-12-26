@extends('admin.layout')

@section('title', 'Badge Details')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">{{ $badge->name }}</h2>
            <p class="text-sm text-slate-600">{{ $badge->description ?: 'No description provided.' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.badges.edit', $badge) }}" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Edit badge</a>
            <form method="POST" action="{{ route('admin.badges.destroy', $badge) }}" onsubmit="return confirm('Delete this badge?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm border border-red-200 text-red-600 rounded hover:bg-red-50">Delete</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-5 space-y-4">
            <div>
                <div class="text-xs uppercase text-slate-500">Preview</div>
                <div class="mt-2">
                    <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded" style="{{ $badge->getInlineStyles() }}">
                        {{ $badge->getDisplayLabel() }}
                    </span>
                </div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Status</div>
                <div class="mt-1 text-sm">{{ $badge->isActive() ? 'Active' : 'Inactive' }}</div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Type</div>
                <div class="mt-1 text-sm">{{ ucfirst($badge->type) }}</div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Position</div>
                <div class="mt-1 text-sm">{{ $badge->position }}</div>
            </div>
            <div>
                <div class="text-xs uppercase text-slate-500">Priority</div>
                <div class="mt-1 text-sm">{{ $badge->priority }}</div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Assignments</h3>
                <div class="space-y-3">
                    @forelse($badge->assignments as $assignment)
                        <div class="flex items-center justify-between border border-slate-200 rounded px-4 py-3">
                            <div>
                                <div class="font-medium">
                                    {{ $assignment->product ? $assignment->product->translateAttribute('name') : 'Unknown product' }}
                                </div>
                                <div class="text-xs text-slate-500">Assigned {{ $assignment->assigned_at?->format('M j, Y') ?? 'N/A' }}</div>
                            </div>
                            <div class="text-xs text-slate-500">{{ $assignment->assignment_type ?? 'manual' }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No assignments yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Rules</h3>
                <div class="space-y-3">
                    @forelse($badge->rules as $rule)
                        <div class="flex items-center justify-between border border-slate-200 rounded px-4 py-3">
                            <div>
                                <div class="font-medium">{{ $rule->name ?? 'Rule #' . $rule->id }}</div>
                                <div class="text-xs text-slate-500">{{ ucfirst($rule->condition_type) }} - Priority {{ $rule->priority }}</div>
                            </div>
                            <div class="text-xs">{{ $rule->is_active ? 'Active' : 'Inactive' }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No rules configured.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Performance</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="rounded border border-slate-200 px-3 py-2">
                        <div class="text-xs text-slate-500">Total assignments</div>
                        <div class="text-lg font-semibold">{{ $badge->assignments->count() }}</div>
                    </div>
                    <div class="rounded border border-slate-200 px-3 py-2">
                        <div class="text-xs text-slate-500">Rules</div>
                        <div class="text-lg font-semibold">{{ $badge->rules->count() }}</div>
                    </div>
                    <div class="rounded border border-slate-200 px-3 py-2">
                        <div class="text-xs text-slate-500">Performance samples</div>
                        <div class="text-lg font-semibold">{{ $badge->performance->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
