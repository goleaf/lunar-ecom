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
        <div id="schedule-list" class="text-sm text-slate-600">Loading schedules...</div>
    </div>
</div>

@push('scripts')
<script>
const scheduleList = document.getElementById('schedule-list');
const start = new Date();
const end = new Date(start.getFullYear(), start.getMonth() + 1, 0);

const params = new URLSearchParams({
    start: start.toISOString().slice(0, 10),
    end: end.toISOString().slice(0, 10)
});

fetch(`{{ route('admin.schedules.calendar.schedules') }}?${params}`)
    .then((response) => response.json())
    .then((data) => {
        if (!Array.isArray(data) || data.length === 0) {
            scheduleList.textContent = 'No schedules found for this month.';
            return;
        }

        const items = data.map((item) => {
            const startDate = new Date(item.start).toLocaleDateString();
            return `<div class="flex items-center justify-between border border-slate-200 rounded px-4 py-3">
                <div>
                    <div class="font-semibold">${item.title}</div>
                    <div class="text-xs text-slate-500">${startDate} | ${item.type}</div>
                </div>
                <a href="${item.url}" class="text-blue-600 hover:underline text-sm">View</a>
            </div>`;
        });

        scheduleList.innerHTML = `<div class="space-y-3">${items.join('')}</div>`;
    })
    .catch(() => {
        scheduleList.textContent = 'Failed to load schedules.';
    });
</script>
@endpush
@endsection
