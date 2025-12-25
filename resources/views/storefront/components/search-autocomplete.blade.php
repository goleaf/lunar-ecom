{{-- Search Autocomplete Component --}}
<div x-data="searchAutocomplete()" class="relative">
    <form action="{{ route('storefront.search.index') }}" method="GET" @submit.prevent="handleSubmit">
        <div class="relative">
            <input 
                type="text" 
                name="q" 
                x-model="query"
                @input.debounce.300ms="search()"
                @focus="showDropdown = true"
                @click.away="showDropdown = false"
                @keydown.escape="showDropdown = false"
                @keydown.arrow-down.prevent="navigateDown()"
                @keydown.arrow-up.prevent="navigateUp()"
                @keydown.enter.prevent="selectSuggestion()"
                placeholder="{{ __('storefront.nav.search_placeholder') }}"
                class="w-full border rounded px-4 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                autocomplete="off">
            
            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
        </div>

        {{-- Dropdown with suggestions --}}
        <div 
            x-show="showDropdown && (suggestions.length > 0 || history.length > 0 || query.length > 0)"
            x-transition
            class="absolute z-50 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-96 overflow-y-auto">
            
            {{-- Search History --}}
            <template x-if="history.length > 0 && query.length === 0">
                <div class="p-2 border-b">
                    <div class="text-xs text-gray-500 mb-2 px-2">Recent Searches</div>
                    <template x-for="(item, index) in history" :key="index">
                        <a 
                            :href="`{{ route('storefront.search.index') }}?q=${encodeURIComponent(item)}`"
                            class="block px-4 py-2 hover:bg-gray-100 text-sm"
                            x-text="item">
                        </a>
                    </template>
                </div>
            </template>

            {{-- Suggestions --}}
            <template x-if="suggestions.length > 0">
                <div class="p-2">
                    <template x-for="(suggestion, index) in suggestions" :key="index">
                        <a 
                            :href="suggestion.url"
                            :class="{'bg-blue-50': index === selectedIndex}"
                            class="block px-4 py-2 hover:bg-gray-100 text-sm flex items-center space-x-2"
                            @mouseenter="selectedIndex = index">
                            <template x-if="suggestion.type === 'product'">
                                <div class="flex items-center space-x-2 flex-1">
                                    <span class="text-gray-400">🔍</span>
                                    <span x-text="suggestion.text"></span>
                                </div>
                            </template>
                            <template x-if="suggestion.type === 'search'">
                                <div class="flex items-center space-x-2 flex-1">
                                    <span class="text-gray-400">📋</span>
                                    <span x-text="suggestion.text"></span>
                                </div>
                            </template>
                        </a>
                    </template>
                </div>
            </template>

            {{-- Popular Searches --}}
            <template x-if="popularSearches.length > 0 && query.length === 0">
                <div class="p-2 border-t">
                    <div class="text-xs text-gray-500 mb-2 px-2">Popular Searches</div>
                    <template x-for="(item, index) in popularSearches" :key="index">
                        <a 
                            :href="`{{ route('storefront.search.index') }}?q=${encodeURIComponent(item.search_term)}`"
                            class="block px-4 py-2 hover:bg-gray-100 text-sm"
                            x-text="item.search_term">
                        </a>
                    </template>
                </div>
            </template>

            {{-- No Results --}}
            <template x-if="query.length >= 2 && suggestions.length === 0 && !loading">
                <div class="p-4 text-center text-gray-500 text-sm">
                    No suggestions found for "<span x-text="query"></span>"
                </div>
            </template>

            {{-- Loading --}}
            <template x-if="loading">
                <div class="p-4 text-center text-gray-500 text-sm">
                    Searching...
                </div>
            </template>
        </div>
    </form>

    <script>
        function searchAutocomplete() {
            return {
                query: '',
                suggestions: [],
                history: [],
                popularSearches: [],
                showDropdown: false,
                selectedIndex: -1,
                loading: false,

                init() {
                    // Load search history and popular searches
                    this.loadHistory();
                    this.loadPopularSearches();
                },

                async search() {
                    if (this.query.length < 2) {
                        this.suggestions = [];
                        return;
                    }

                    this.loading = true;
                    this.showDropdown = true;

                    try {
                        const response = await fetch(`{{ route('storefront.search.autocomplete') }}?q=${encodeURIComponent(this.query)}&limit=10`);
                        const data = await response.json();
                        this.suggestions = data.data || [];
                    } catch (error) {
                        console.error('Search error:', error);
                        this.suggestions = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async loadHistory() {
                    try {
                        const response = await fetch(`{{ route('storefront.search.autocomplete') }}?q=&limit=5`);
                        const data = await response.json();
                        this.history = data.history || [];
                    } catch (error) {
                        console.error('History load error:', error);
                    }
                },

                async loadPopularSearches() {
                    try {
                        const response = await fetch(`{{ route('storefront.search.popular') }}?limit=5`);
                        const data = await response.json();
                        this.popularSearches = data.data || [];
                    } catch (error) {
                        console.error('Popular searches load error:', error);
                    }
                },

                navigateDown() {
                    if (this.selectedIndex < this.suggestions.length - 1) {
                        this.selectedIndex++;
                    }
                },

                navigateUp() {
                    if (this.selectedIndex > 0) {
                        this.selectedIndex--;
                    }
                },

                selectSuggestion() {
                    if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
                        window.location.href = this.suggestions[this.selectedIndex].url;
                    } else if (this.query.length > 0) {
                        this.handleSubmit();
                    }
                },

                handleSubmit() {
                    if (this.query.length > 0) {
                        window.location.href = `{{ route('storefront.search.index') }}?q=${encodeURIComponent(this.query)}`;
                    }
                }
            }
        }
    </script>
</div>

