<div>
    <div class="mb-4">
        <h3 class="text-lg font-semibold">Change History</h3>
        <p class="text-sm text-gray-600">Track all changes to this variant</p>
    </div>

    <div class="space-y-4">
        @foreach($history as $entry)
            <div class="flex items-start space-x-4 p-4 border rounded-lg">
                <div class="flex-shrink-0">
                    @if($entry['type'] === 'created')
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                    @elseif($entry['type'] === 'updated')
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </div>
                    @else
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    @endif
                </div>
                
                <div class="flex-1">
                    <div class="font-medium capitalize">{{ $entry['type'] }}</div>
                    <div class="text-sm text-gray-500">{{ $entry['user'] }}</div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $entry['timestamp']->format('M d, Y H:i') }}
                    </div>
                    
                    @if(isset($entry['changes']) && is_array($entry['changes']))
                        <div class="mt-2 text-sm">
                            @foreach($entry['changes'] as $change)
                                <div class="text-gray-600">• {{ $change }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>


