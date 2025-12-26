@extends('frontend.layout')

@section('title', 'Review Guidelines')

@section('content')
<div class="px-4 py-6 max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Review Guidelines</h1>

    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <h2 class="text-xl font-semibold mb-3">Review Requirements</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li>Title: {{ $guidelines['title_min'] }}-{{ $guidelines['title_max'] }} characters</li>
                <li>Content: {{ $guidelines['content_min'] }}-{{ $guidelines['content_max'] }} characters</li>
                <li>Rating: {{ implode(', ', $guidelines['rating_range']) }} stars</li>
                <li>Images: Maximum {{ $guidelines['max_images'] }} images per review</li>
            </ul>
        </div>

        <div>
            <h2 class="text-xl font-semibold mb-3">Guidelines</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                @foreach($guidelines['guidelines'] as $guideline)
                    <li>{{ $guideline }}</li>
                @endforeach
            </ul>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <p class="text-sm text-blue-700">
                <strong>Note:</strong> All reviews are subject to moderation. Reviews that violate our guidelines will be rejected.
            </p>
        </div>

        <div class="pt-4">
            <a href="{{ url()->previous() }}" class="text-blue-600 hover:text-blue-800">← Back</a>
        </div>
    </div>
</div>
@endsection


