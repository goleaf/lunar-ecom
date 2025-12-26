@extends('admin.layout')

@section('title', 'Product Badges')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Product badges</h2>
            <p class="text-sm text-slate-600">Manage badge styles and automatic assignment rules.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.badges.rules.index') }}" class="px-4 py-2 text-sm border border-slate-300 rounded hover:bg-slate-50">Badge rules</a>
            <a href="{{ route('admin.badges.create') }}" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Create badge</a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Badge</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Priority</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Assignments</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($badges as $badge)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $badge->name }}</div>
                            <div class="text-xs text-slate-500">{{ $badge->handle }}</div>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded" style="{{ $badge->getInlineStyles() }}">
                                    {{ $badge->getDisplayLabel() }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ ucfirst($badge->type) }}</td>
                        <td class="px-4 py-3">{{ $badge->priority }}</td>
                        <td class="px-4 py-3">{{ $badge->assignments_count }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $badge->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $badge->isActive() ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.badges.show', $badge) }}" class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('admin.badges.edit', $badge) }}" class="ml-3 text-slate-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.badges.destroy', $badge) }}" class="inline" onsubmit="return confirm('Delete this badge?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No badges found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $badges->links() }}
    </div>
</div>
@endsection
