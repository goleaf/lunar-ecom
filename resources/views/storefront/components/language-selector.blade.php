{{-- Language Selector Component --}}
<div class="relative" x-data="languageSelector()" x-init="init()">
    <button 
        @click="toggleDropdown()"
        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
        type="button"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
        </svg>
        <span x-text="currentLanguage?.code?.toUpperCase() || 'EN'"></span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div 
        x-show="isOpen"
        @click.away="closeDropdown()"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200"
        style="display: none;"
    >
        <div class="py-1">
            <template x-for="language in languages" :key="language.code">
                <button
                    @click="switchLanguage(language.code)"
                    :class="{
                        'bg-blue-50 text-blue-700': language.code === currentLanguage?.code,
                        'text-gray-700 hover:bg-gray-100': language.code !== currentLanguage?.code
                    }"
                    class="w-full text-left px-4 py-2 text-sm flex items-center justify-between"
                    type="button"
                >
                    <span>
                        <span x-text="language.code.toUpperCase()" class="font-medium"></span>
                        <span class="text-gray-500 ml-2" x-text="language.name"></span>
                    </span>
                    <svg 
                        x-show="language.code === currentLanguage?.code"
                        class="w-4 h-4 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </template>
        </div>
    </div>
</div>

<script>
function languageSelector() {
    return {
        isOpen: false,
        languages: [],
        currentLanguage: null,
        loading: false,

        async init() {
            await this.loadLanguages();
        },

        async loadLanguages() {
            try {
                const response = await fetch('{{ route("storefront.language.index") }}');
                const data = await response.json();
                this.languages = data.languages || [];
                this.currentLanguage = data.current || this.languages.find(l => l.is_default) || this.languages[0];
            } catch (error) {
                console.error('Failed to load languages:', error);
            }
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
        },

        closeDropdown() {
            this.isOpen = false;
        },

        async switchLanguage(languageCode) {
            if (this.loading || languageCode === this.currentLanguage?.code) {
                this.closeDropdown();
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route("storefront.language.switch") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ language: languageCode })
                });

                const data = await response.json();

                if (data.success) {
                    this.currentLanguage = data.language;
                    // Reload the page to update all translations
                    window.location.reload();
                } else {
                    alert('Failed to switch language: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to switch language:', error);
                alert('Failed to switch language. Please try again.');
            } finally {
                this.loading = false;
                this.closeDropdown();
            }
        }
    }
}
</script>

