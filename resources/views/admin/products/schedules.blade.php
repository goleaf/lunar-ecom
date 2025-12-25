@extends('admin.layout')

@section('title', 'Product Schedules - ' . $product->translateAttribute('name'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Product Schedules</h1>
        <p class="text-gray-600 mt-2">{{ $product->translateAttribute('name') }}</p>
    </div>

    <!-- Create Schedule Form -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-semibold mb-4">Create New Schedule</h2>
        
        <form id="schedule-form" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Type</label>
                    <select name="type" id="schedule-type" required class="w-full border rounded px-3 py-2">
                        <option value="publish">Publish Product</option>
                        <option value="unpublish">Unpublish Product</option>
                        <option value="flash_sale">Flash Sale</option>
                        <option value="seasonal">Seasonal Product</option>
                        <option value="time_limited">Time-Limited Offer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Scheduled Date & Time</label>
                    <input type="datetime-local" name="scheduled_at" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Status</label>
                    <select name="target_status" class="w-full border rounded px-3 py-2">
                        <option value="">Use Default</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>

            <!-- Flash Sale Options -->
            <div id="flash-sale-options" class="hidden space-y-4 border-t pt-4 mt-4">
                <h3 class="font-semibold">Flash Sale Settings</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sale Price</label>
                        <input type="number" name="sale_price" step="0.01" min="0" class="w-full border rounded px-3 py-2" placeholder="e.g., 29.99">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sale Percentage</label>
                        <input type="number" name="sale_percentage" min="0" max="100" class="w-full border rounded px-3 py-2" placeholder="e.g., 25">
                    </div>
                </div>

                <label class="flex items-center">
                    <input type="checkbox" name="restore_original_price" value="1" checked class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Restore original price after sale ends</span>
                </label>
            </div>

            <!-- Recurring Options -->
            <div class="space-y-4 border-t pt-4 mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_recurring" value="1" id="is-recurring" class="rounded">
                    <span class="ml-2 text-sm font-medium text-gray-700">Recurring Schedule</span>
                </label>

                <div id="recurring-options" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recurrence Pattern</label>
                    <select name="recurrence_pattern" class="w-full border rounded px-3 py-2">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="send_notification" value="1" class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Send Notification</span>
                </label>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Create Schedule
            </button>
        </form>
    </div>

    <!-- Schedules List -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold mb-4">Scheduled Events</h2>
        
        @if($schedules->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scheduled At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($schedules as $schedule)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded {{ $this->getTypeColor($schedule->type) }}">
                                        {{ ucfirst(str_replace('_', ' ', $schedule->type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ $schedule->scheduled_at->format('M j, Y H:i') }}
                                    @if($schedule->scheduled_at->isPast())
                                        <span class="text-green-600 text-xs">(Executed)</span>
                                    @else
                                        <span class="text-blue-600 text-xs">({{ $schedule->scheduled_at->diffForHumans() }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($schedule->expires_at)
                                        {{ $schedule->expires_at->format('M j, Y H:i') }}
                                        @if($schedule->expires_at->isPast())
                                            <span class="text-red-600 text-xs">(Expired)</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($schedule->is_active)
                                        <span class="text-green-600 text-sm">Active</span>
                                    @else
                                        <span class="text-gray-400 text-sm">Inactive</span>
                                    @endif
                                    @if($schedule->is_recurring)
                                        <span class="text-blue-600 text-xs ml-2">(Recurring)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="editSchedule({{ $schedule->id }})" class="text-blue-600 hover:underline mr-2">Edit</button>
                                    <button onclick="deleteSchedule({{ $schedule->id }})" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $schedules->links() }}
            </div>
        @else
            <p class="text-gray-600">No schedules found for this product.</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Show/hide flash sale options
document.getElementById('schedule-type').addEventListener('change', function() {
    const flashSaleOptions = document.getElementById('flash-sale-options');
    if (this.value === 'flash_sale') {
        flashSaleOptions.classList.remove('hidden');
    } else {
        flashSaleOptions.classList.add('hidden');
    }
});

// Show/hide recurring options
document.getElementById('is-recurring').addEventListener('change', function() {
    const recurringOptions = document.getElementById('recurring-options');
    if (this.checked) {
        recurringOptions.classList.remove('hidden');
    } else {
        recurringOptions.classList.add('hidden');
    }
});

// Handle form submission
document.getElementById('schedule-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('{{ route('admin.products.schedules.store', $product->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Schedule created successfully');
            location.reload();
        } else {
            alert('Failed to create schedule: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to create schedule');
    }
});

function deleteSchedule(scheduleId) {
    if (!confirm('Are you sure you want to delete this schedule?')) return;
    
    fetch(`/admin/products/{{ $product->id }}/schedules/${scheduleId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete schedule');
        }
    });
}

function editSchedule(scheduleId) {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon');
}
</script>
@endpush

@php
function getTypeColor($type) {
    return match($type) {
        'publish' => 'bg-green-100 text-green-800',
        'unpublish' => 'bg-red-100 text-red-800',
        'flash_sale' => 'bg-yellow-100 text-yellow-800',
        'seasonal' => 'bg-blue-100 text-blue-800',
        'time_limited' => 'bg-purple-100 text-purple-800',
        default => 'bg-gray-100 text-gray-800',
    };
}
@endphp
@endsection

