@extends('admin.layout')

@section('title', 'Product Questions')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Product Q&A moderation</h2>
        <p class="text-sm text-slate-600">Review and approve product questions.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Pending</div>
            <div class="text-2xl font-semibold">{{ $stats['pending'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Approved</div>
            <div class="text-2xl font-semibold">{{ $stats['approved'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Rejected</div>
            <div class="text-2xl font-semibold">{{ $stats['rejected'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-slate-500">Unanswered</div>
            <div class="text-2xl font-semibold">{{ $stats['unanswered'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('admin.products.questions.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs text-slate-600 mb-1">Status</label>
                <select name="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['pending', 'approved', 'rejected'] as $option)
                        <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Question</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Asked</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($questions as $question)
                    <tr>
                        <td class="px-4 py-3">{{ $question->question }}</td>
                        <td class="px-4 py-3">{{ $question->product?->translateAttribute('name') ?? 'Unknown' }}</td>
                        <td class="px-4 py-3">{{ $question->customer?->name ?? 'Guest' }}</td>
                        <td class="px-4 py-3">{{ $question->asked_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3">{{ ucfirst($question->status) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.questions.show', $question) }}" class="text-blue-600 hover:underline">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No questions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $questions->links() }}
    </div>
</div>
@endsection
