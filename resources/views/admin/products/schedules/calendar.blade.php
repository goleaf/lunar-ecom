@extends('admin.layout')

@section('title', 'Schedule Calendar')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Schedule calendar</h2>
        <p class="text-sm text-slate-600">Upcoming publish, unpublish, and promo schedules.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Upcoming schedules</h3>
        <div id="schedule-list" class="text-sm text-slate-600" data-url="{{ route('admin.schedules.calendar.schedules') }}">Loading schedules...</div>
    </div>
</div>

@push('scripts')
@endpush
@endsection
