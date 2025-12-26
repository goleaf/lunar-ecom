{{-- Attribute Filters Component --}}
@php
    use App\Lunar\Attributes\AttributeFilterHelper;
    $groupedAttributes = $groupedAttributes ?? collect();
    $activeFilters = $activeFilters ?? [];
    $baseUrl = $baseUrl ?? request()->url();
@endphp

@if($groupedAttributes->count() > 0)
    <div class="attribute-filters space-y-6">
        @foreach($groupedAttributes as $group)
            @if($group['attributes']->count() > 0)
                <div class="filter-group">
                    <h3 class="font-semibold text-lg mb-3">{{ $group['name'] }}</h3>
                    
                    @foreach($group['attributes'] as $attribute)
                        <div class="filter-attribute mb-4">
                            <label class="block text-sm font-medium mb-2">
                                {{ AttributeFilterHelper::getFilterDisplayName($attribute) }}
                            </label>

                            @if($attribute['is_numeric'])
                                {{-- Numeric Range Filter --}}
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <input type="number" 
                                               name="{{ $attribute['handle'] }}[min]" 
                                               value="{{ request($attribute['handle'] . '.min') }}" 
                                               placeholder="Min {{ $attribute['unit'] ?? '' }}"
                                               min="{{ $attribute['options']['min'] ?? 0 }}"
                                               max="{{ $attribute['options']['max'] ?? 0 }}"
                                               step="0.01"
                                               class="border rounded px-3 py-1 w-24 text-sm">
                                        <span class="text-gray-500">-</span>
                                        <input type="number" 
                                               name="{{ $attribute['handle'] }}[max]" 
                                               value="{{ request($attribute['handle'] . '.max') }}" 
                                               placeholder="Max {{ $attribute['unit'] ?? '' }}"
                                               min="{{ $attribute['options']['min'] ?? 0 }}"
                                               max="{{ $attribute['options']['max'] ?? 0 }}"
                                               step="0.01"
                                               class="border rounded px-3 py-1 w-24 text-sm">
                                    </div>
                                    @if(isset($attribute['options']['min']) && isset($attribute['options']['max']))
                                        <p class="text-xs text-gray-500">
                                            Range: {{ $attribute['options']['min'] }}{{ $attribute['unit'] ?? '' }} - {{ $attribute['options']['max'] }}{{ $attribute['unit'] ?? '' }}
                                        </p>
                                    @endif
                                </div>

                            @elseif($attribute['is_color'])
                                {{-- Color Filter --}}
                                <div class="flex flex-wrap gap-2">
                                    @foreach($attribute['options'] as $option)
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" 
                                                   name="{{ $attribute['handle'] }}[]" 
                                                   value="{{ $option['value'] }}"
                                                   {{ AttributeFilterHelper::isFilterActive($attribute['handle'], $option['value'], $activeFilters) ? 'checked' : '' }}
                                                   class="sr-only">
                                            <div class="flex items-center space-x-1">
                                                <div class="w-6 h-6 rounded border-2 {{ AttributeFilterHelper::isFilterActive($attribute['handle'], $option['value'], $activeFilters) ? 'border-blue-600' : 'border-gray-300' }}" 
                                                     style="background-color: {{ $option['hex'] ?? '#CCCCCC' }}"
                                                     title="{{ $option['label'] }} ({{ $option['count'] }})">
                                                </div>
                                                <span class="text-sm text-gray-600">{{ $option['count'] }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                            @elseif($attribute['is_boolean'])
                                {{-- Boolean Filter --}}
                                <div class="space-y-1">
                                    @foreach($attribute['options'] as $option)
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="radio" 
                                                   name="{{ $attribute['handle'] }}" 
                                                   value="{{ $option['value'] ? '1' : '0' }}"
                                                   {{ AttributeFilterHelper::isFilterActive($attribute['handle'], $option['value'] ? '1' : '0', $activeFilters) ? 'checked' : '' }}
                                                   class="rounded">
                                            <span class="text-sm">{{ $option['label'] }} ({{ $option['count'] }})</span>
                                        </label>
                                    @endforeach
                                </div>

                            @else
                                {{-- Select/Multiselect/Text Filter --}}
                                <div class="space-y-1 max-h-48 overflow-y-auto">
                                    @foreach($attribute['options'] as $option)
                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                            <input type="checkbox" 
                                                   name="{{ $attribute['handle'] }}[]" 
                                                   value="{{ $option['value'] }}"
                                                   {{ AttributeFilterHelper::isFilterActive($attribute['handle'], $option['value'], $activeFilters) ? 'checked' : '' }}
                                                   class="rounded">
                                            <span class="text-sm flex-1">{{ $option['label'] }}</span>
                                            <span class="text-xs text-gray-500">({{ $option['count'] }})</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
@endif

