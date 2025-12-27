@extends('admin.layout')

@section('title', 'Question Review')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Question review</h2>
        <p class="text-sm text-slate-600">{{ $question->product?->translateAttribute('name') ?? 'Product' }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <div>
            <div class="text-xs uppercase text-slate-500">Question</div>
            <div class="mt-2 text-lg font-semibold">{{ $question->question }}</div>
        </div>
        <div class="text-sm text-slate-600">
            Asked by {{ $question->customer?->name ?? 'Guest' }} on {{ $question->asked_at?->format('M j, Y H:i') }}
        </div>
        <div class="flex flex-wrap gap-4 text-sm">
            <span>Status: {{ ucfirst($question->status) }}</span>
            <span>Helpful: {{ $question->helpful_count }}</span>
            <span>Views: {{ $question->views_count }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Moderate question</h3>
            <form id="question-moderate-form" data-url="{{ route('admin.products.questions.moderate', $question) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                        <option value="spam">Spam</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Submit</button>
                    <span id="question-moderate-message" class="text-sm text-slate-600"></span>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Answer question</h3>
            <form id="question-answer-form" data-url="{{ route('admin.products.questions.answer', $question) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Answer</label>
                    <textarea name="answer" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required></textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_official" value="0">
                    <input type="checkbox" name="is_official" value="1" class="rounded" checked>
                    Official response
                </label>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded">Send answer</button>
                    <span id="question-answer-message" class="text-sm text-slate-600"></span>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Answers</h3>
        <div class="space-y-4">
            @forelse($question->answers as $answer)
                <div class="border border-slate-200 rounded p-4 space-y-3">
                    <div class="text-sm">{{ $answer->answer }}</div>
                    <div class="text-xs text-slate-500">Status: {{ ucfirst($answer->status) }} | Helpful: {{ $answer->helpful_count }}</div>
                    <form class="answer-moderate-form" data-url="{{ route('admin.products.questions.answer.moderate', ['question' => $question->id, 'answer' => $answer->id]) }}">
                        @csrf
                        <div class="flex flex-wrap items-end gap-3">
                            <select name="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                            <input type="text" name="notes" class="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Notes">
                            <button type="submit" class="px-3 py-2 text-sm bg-slate-800 text-white rounded">Update</button>
                        </div>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">No answers yet.</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
@endpush
@endsection
