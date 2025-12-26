@extends('storefront.layout')

@section('title', 'Questions & Answers - ' . $product->translateAttribute('name'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-3xl font-bold text-gray-900">Questions & Answers</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $product->translateAttribute('name') }}</p>
        <p class="text-sm text-gray-500 mt-2">{{ $qaCount ?? $questions->total() }} total questions</p>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Ask a question</h2>
        <form id="question-form" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Your question</label>
                <textarea name="question" rows="4" class="w-full rounded border border-gray-300 px-3 py-2" required></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="customer_name" class="w-full rounded border border-gray-300 px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" class="w-full rounded border border-gray-300 px-3 py-2" required>
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="hidden" name="is_public" value="0">
                <input type="checkbox" name="is_public" value="1" class="rounded" checked>
                Show my question publicly
            </label>
            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit question</button>
                <span id="question-message" class="text-sm text-gray-600"></span>
            </div>
        </form>
    </div>

    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h2 class="text-xl font-semibold">Questions</h2>
            <form method="GET" class="flex flex-wrap gap-2 text-sm">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search questions" class="rounded border border-gray-300 px-3 py-2">
                <select name="answered" class="rounded border border-gray-300 px-3 py-2">
                    <option value="">All</option>
                    <option value="1" {{ request('answered') === '1' ? 'selected' : '' }}>Answered</option>
                    <option value="0" {{ request('answered') === '0' ? 'selected' : '' }}>Unanswered</option>
                </select>
                <select name="sort" class="rounded border border-gray-300 px-3 py-2">
                    <option value="helpful" {{ request('sort', 'helpful') === 'helpful' ? 'selected' : '' }}>Most helpful</option>
                    <option value="recent" {{ request('sort') === 'recent' ? 'selected' : '' }}>Most recent</option>
                </select>
                <button type="submit" class="px-3 py-2 bg-gray-900 text-white rounded">Filter</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($questions as $question)
                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="font-semibold text-gray-900">{{ $question->question }}</div>
                    <div class="text-xs text-gray-500">Asked {{ $question->asked_at?->format('M j, Y') }} by {{ $question->customer_name ?? 'Customer' }}</div>
                    <div class="flex flex-wrap gap-3 text-xs text-gray-500">
                        <button type="button" class="mark-helpful text-blue-600" data-url="{{ route('storefront.products.questions.helpful', ['product' => $product->id, 'question' => $question->id]) }}">Helpful ({{ $question->helpful_count }})</button>
                        <span>Views: {{ $question->views_count }}</span>
                    </div>

                    <div class="space-y-3">
                        @forelse($question->answers as $answer)
                            <div class="border-l-2 border-blue-200 pl-4">
                                <div class="text-sm text-gray-800">{{ $answer->answer }}</div>
                                <div class="text-xs text-gray-500 mt-1">Answered {{ $answer->answered_at?->format('M j, Y') ?? $answer->created_at?->format('M j, Y') }}</div>
                                <button type="button" class="mark-helpful text-xs text-blue-600" data-url="{{ route('storefront.products.questions.answer.helpful', ['product' => $product->id, 'question' => $question->id, 'answer' => $answer->id]) }}">Helpful ({{ $answer->helpful_count }})</button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No answers yet.</p>
                        @endforelse
                    </div>

                    <form class="answer-form pt-3 border-t border-gray-200" data-url="{{ route('storefront.products.questions.answer', ['product' => $product->id, 'question' => $question->id]) }}">
                        @csrf
                        <label class="block text-xs text-gray-600 mb-1">Add an answer</label>
                        <textarea name="answer" rows="2" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" required></textarea>
                        <button type="submit" class="mt-2 px-3 py-2 bg-gray-900 text-white rounded text-sm">Submit answer</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">No questions found.</p>
            @endforelse
        </div>

        <div>
            {{ $questions->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
const questionForm = document.getElementById('question-form');
const questionMessage = document.getElementById('question-message');

questionForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    questionMessage.textContent = 'Submitting...';

    const formData = new FormData(questionForm);
    const payload = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('{{ route('storefront.products.questions.store', $product) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (response.ok) {
            questionMessage.textContent = data.message || 'Question submitted.';
            setTimeout(() => window.location.reload(), 800);
        } else {
            questionMessage.textContent = data.message || 'Unable to submit question.';
        }
    } catch (error) {
        questionMessage.textContent = 'Unable to submit question.';
    }
});

document.querySelectorAll('.answer-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(form.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            alert('Failed to submit answer.');
        }
    });
});

document.querySelectorAll('.mark-helpful').forEach((button) => {
    button.addEventListener('click', async () => {
        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            alert('Unable to mark helpful.');
        }
    });
});
</script>
@endpush
@endsection
