@extends('admin.layout')

@section('title', 'Search Analytics')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Search analytics</h2>
            <p class="text-sm text-slate-600">Track search demand and zero-result queries.</p>
        </div>
        <div>
            <a href="{{ route('admin.search-analytics.synonyms') }}" class="px-4 py-2 text-sm border border-slate-300 rounded hover:bg-slate-50">Manage synonyms</a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Total searches</div>
            <div class="text-2xl font-semibold">{{ $stats['total_searches'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Zero results</div>
            <div class="text-2xl font-semibold">{{ $stats['zero_result_searches'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Zero result %</div>
            <div class="text-2xl font-semibold">{{ $stats['zero_result_percentage'] }}%</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Unique terms</div>
            <div class="text-2xl font-semibold">{{ $stats['unique_searches'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Click-through</div>
            <div class="text-2xl font-semibold">{{ $stats['click_through_rate'] }}%</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold mb-4">Popular searches</h3>
            <div class="space-y-3">
                @forelse($popularSearches as $search)
                    <div class="flex items-center justify-between border border-slate-200 rounded px-4 py-2">
                        <div class="font-medium">{{ $search->search_term ?? $search->term ?? 'Term' }}</div>
                        <div class="text-sm text-slate-600">{{ $search->count ?? 0 }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No popular searches yet.</p>
                @endforelse
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold mb-4">Zero results</h3>
            <div class="space-y-3">
                @forelse($zeroResults as $zero)
                    <div class="flex items-center justify-between border border-slate-200 rounded px-4 py-2">
                        <div class="font-medium">{{ $zero->search_term }}</div>
                        <div class="text-sm text-slate-600">{{ $zero->count }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No zero result searches.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold mb-4">Recent searches</h3>
        <div class="space-y-2 text-sm">
            @forelse($recentSearches as $search)
                <div class="flex items-center justify-between border border-slate-200 rounded px-4 py-2">
                    <div>{{ $search->search_term }}</div>
                    <div class="text-xs text-slate-500">{{ $search->searched_at?->format('M j, Y H:i') }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No recent searches.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
