@extends('admin.layout')

@section('title', 'Product Badges')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Badges for {{ $product->translateAttribute('name') }}</h2>
        <p class="text-sm text-slate-600">Assign and manage badge visibility for this product.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold mb-4">Assigned badges</h3>
            <div class="space-y-3">
                @forelse($badges as $badge)
                    <div class="flex items-center justify-between border border-slate-200 rounded px-4 py-3">
                        <div>
                            <div class="font-medium">{{ $badge->name }}</div>
                            <div class="text-xs text-slate-500">{{ ucfirst($badge->type) }}</div>
                        </div>
                        <button type="button" class="text-sm text-red-600 hover:underline badge-remove" data-url="{{ route('admin.badges.products.remove', ['product' => $product->id, 'badge' => $badge->id]) }}">Remove</button>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No badges assigned.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold mb-4">Assign badge</h3>
            <form id="badge-assign-form" class="space-y-4" data-url="{{ route('admin.badges.products.assign', $product) }}">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Badge</label>
                    <select name="badge_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                        <option value="">Select badge</option>
                        @foreach($allBadges as $badge)
                            <option value="{{ $badge->id }}">{{ $badge->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
                        <input type="number" name="priority" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" min="0" max="100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Position</label>
                        <select name="display_position" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Default</option>
                            <option value="top-left">Top left</option>
                            <option value="top-right">Top right</option>
                            <option value="bottom-left">Bottom left</option>
                            <option value="bottom-right">Bottom right</option>
                            <option value="center">Center</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Starts at</label>
                        <input type="datetime-local" name="starts_at" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Expires at</label>
                        <input type="datetime-local" name="expires_at" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Assign badge</button>
                    <span id="badge-assign-message" class="text-sm text-slate-600"></span>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@endpush
@endsection
