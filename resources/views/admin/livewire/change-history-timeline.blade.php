<div>
    <div class="space-y-4">
        <h3 class="text-lg font-semibold">Change History</h3>
        
        <div class="flow-root">
            <ul class="-mb-8">
                @foreach($history as $index => $item)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white bg-blue-500">
                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium text-gray-900">{{ $item['user'] }}</span>
                                            {{ $item['action'] }}
                                            @if($item['from_status'] && $item['to_status'])
                                                from <span class="font-medium">{{ $item['from_status'] }}</span>
                                                to <span class="font-medium">{{ $item['to_status'] }}</span>
                                            @endif
                                        </p>
                                        @if($item['notes'])
                                            <p class="mt-1 text-sm text-gray-600">{{ $item['notes'] }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                        {{ $item['timestamp']->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

