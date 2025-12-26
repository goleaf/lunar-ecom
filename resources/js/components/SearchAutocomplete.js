/**
 * Search Autocomplete Component
 * 
 * Provides instant search suggestions with:
 * - Real-time autocomplete
 * - Search history
 * - Trending searches
 * - Product suggestions
 */

class SearchAutocomplete {
    constructor(inputId, options = {}) {
        this.input = document.getElementById(inputId);
        if (!this.input) {
            console.error(`Input #${inputId} not found`);
            return;
        }

        this.options = {
            apiUrl: options.apiUrl || '/search/autocomplete',
            minLength: options.minLength || 2,
            debounceMs: options.debounceMs || 300,
            maxSuggestions: options.maxSuggestions || 10,
            showHistory: options.showHistory !== false,
            showTrending: options.showTrending !== false,
            onSelect: options.onSelect || null,
            ...options
        };

        this.suggestions = [];
        this.history = [];
        this.isOpen = false;
        this.selectedIndex = -1;
        this.debounceTimer = null;

        this.init();
    }

    /**
     * Initialize the autocomplete component.
     */
    init() {
        this.createDropdown();
        this.attachEventListeners();
        this.loadHistory();
        if (this.options.showTrending) {
            this.loadTrending();
        }
    }

    /**
     * Create dropdown container.
     */
    createDropdown() {
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'search-autocomplete-dropdown';
        this.dropdown.style.display = 'none';
        this.input.parentNode.appendChild(this.dropdown);
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Input events
        this.input.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });

        this.input.addEventListener('focus', () => {
            if (this.input.value.length >= this.options.minLength) {
                this.handleInput(this.input.value);
            } else if (this.options.showHistory && this.history.length > 0) {
                this.showHistory();
            } else if (this.options.showTrending) {
                this.showTrending();
            }
        });

        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.dropdown.contains(e.target)) {
                this.close();
            }
        });
    }

    /**
     * Handle input changes.
     */
    handleInput(value) {
        clearTimeout(this.debounceTimer);

        if (value.length < this.options.minLength) {
            this.close();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(value);
        }, this.options.debounceMs);
    }

    /**
     * Fetch suggestions from API.
     */
    async fetchSuggestions(query) {
        try {
            const response = await fetch(`${this.options.apiUrl}?q=${encodeURIComponent(query)}&limit=${this.options.maxSuggestions}`);
            const data = await response.json();
            
            this.suggestions = data.data || [];
            this.history = data.history || [];
            
            this.render();
        } catch (error) {
            console.error('Error fetching suggestions:', error);
        }
    }

    /**
     * Load search history.
     */
    async loadHistory() {
        try {
            const response = await fetch(`${this.options.apiUrl}?q=&limit=5`);
            const data = await response.json();
            this.history = data.history || [];
        } catch (error) {
            console.error('Error loading history:', error);
        }
    }

    /**
     * Load trending searches.
     */
    async loadTrending() {
        try {
            const response = await fetch('/search/trending?limit=5');
            const data = await response.json();
            this.trending = data.data || [];
        } catch (error) {
            console.error('Error loading trending:', error);
        }
    }

    /**
     * Render dropdown content.
     */
    render() {
        if (this.suggestions.length === 0 && (!this.options.showHistory || this.history.length === 0)) {
            this.close();
            return;
        }

        let html = '';

        // Show suggestions
        if (this.suggestions.length > 0) {
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-header">Suggestions</div>';
            this.suggestions.forEach((suggestion, index) => {
                html += this.renderSuggestion(suggestion, index);
            });
            html += '</div>';
        }

        // Show history
        if (this.options.showHistory && this.history.length > 0 && this.input.value.length < this.options.minLength) {
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-header">Recent Searches</div>';
            this.history.forEach((term, index) => {
                html += `<div class="autocomplete-item" data-index="${index}" data-type="history" data-value="${term}">
                    <span class="autocomplete-icon">🕒</span>
                    <span class="autocomplete-text">${this.escapeHtml(term)}</span>
                </div>`;
            });
            html += '</div>';
        }

        this.dropdown.innerHTML = html;
        this.open();
        this.attachSuggestionListeners();
    }

    /**
     * Render a single suggestion.
     */
    renderSuggestion(suggestion, index) {
        const icon = suggestion.type === 'product' ? '📦' : '🔍';
        return `
            <div class="autocomplete-item ${index === this.selectedIndex ? 'selected' : ''}" 
                 data-index="${index}" 
                 data-type="${suggestion.type}" 
                 data-value="${suggestion.text}"
                 data-url="${suggestion.url || ''}">
                <span class="autocomplete-icon">${icon}</span>
                <span class="autocomplete-text">${this.escapeHtml(suggestion.text)}</span>
            </div>
        `;
    }

    /**
     * Show search history.
     */
    showHistory() {
        if (this.history.length === 0) {
            return;
        }

        let html = '<div class="autocomplete-section">';
        html += '<div class="autocomplete-header">Recent Searches</div>';
        this.history.forEach((term, index) => {
            html += `<div class="autocomplete-item" data-index="${index}" data-type="history" data-value="${term}">
                <span class="autocomplete-icon">🕒</span>
                <span class="autocomplete-text">${this.escapeHtml(term)}</span>
            </div>`;
        });
        html += '</div>';

        this.dropdown.innerHTML = html;
        this.open();
        this.attachSuggestionListeners();
    }

    /**
     * Show trending searches.
     */
    showTrending() {
        if (!this.trending || this.trending.length === 0) {
            return;
        }

        let html = '<div class="autocomplete-section">';
        html += '<div class="autocomplete-header">Trending Searches</div>';
        this.trending.forEach((item, index) => {
            html += `<div class="autocomplete-item" data-index="${index}" data-type="trending" data-value="${item.search_term}">
                <span class="autocomplete-icon">🔥</span>
                <span class="autocomplete-text">${this.escapeHtml(item.search_term)}</span>
                <span class="autocomplete-count">${item.search_count}</span>
            </div>`;
        });
        html += '</div>';

        this.dropdown.innerHTML = html;
        this.open();
        this.attachSuggestionListeners();
    }

    /**
     * Attach listeners to suggestion items.
     */
    attachSuggestionListeners() {
        this.dropdown.querySelectorAll('.autocomplete-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                this.selectSuggestion(item);
            });

            item.addEventListener('mouseenter', () => {
                this.selectedIndex = index;
                this.updateSelection();
            });
        });
    }

    /**
     * Handle keyboard navigation.
     */
    handleKeydown(e) {
        const items = this.dropdown.querySelectorAll('.autocomplete-item');
        
        if (items.length === 0) {
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.updateSelection();
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection();
                break;

            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                    this.selectSuggestion(items[this.selectedIndex]);
                } else {
                    // Submit search form
                    this.input.form?.submit();
                }
                break;

            case 'Escape':
                this.close();
                break;
        }
    }

    /**
     * Update selection highlighting.
     */
    updateSelection() {
        const items = this.dropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });

        // Scroll into view
        if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
            items[this.selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    /**
     * Select a suggestion.
     */
    selectSuggestion(item) {
        const type = item.dataset.type;
        const value = item.dataset.value;
        const url = item.dataset.url;

        if (url) {
            window.location.href = url;
        } else {
            this.input.value = value;
            this.close();
            
            if (this.options.onSelect) {
                this.options.onSelect(value, type);
            } else {
                // Submit search form
                this.input.form?.submit();
            }
        }
    }

    /**
     * Open dropdown.
     */
    open() {
        this.dropdown.style.display = 'block';
        this.isOpen = true;
        this.positionDropdown();
    }

    /**
     * Close dropdown.
     */
    close() {
        this.dropdown.style.display = 'none';
        this.isOpen = false;
        this.selectedIndex = -1;
    }

    /**
     * Position dropdown below input.
     */
    positionDropdown() {
        const rect = this.input.getBoundingClientRect();
        this.dropdown.style.top = `${rect.bottom + window.scrollY}px`;
        this.dropdown.style.left = `${rect.left + window.scrollX}px`;
        this.dropdown.style.width = `${rect.width}px`;
    }

    /**
     * Escape HTML to prevent XSS.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchAutocomplete;
}


