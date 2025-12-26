/**
 * Product Attribute Filter Component
 * 
 * Handles dynamic product filtering by attributes with AJAX updates.
 * Supports:
 * - Multiple attribute selection
 * - Range sliders for numeric attributes
 * - Color swatches for color attributes
 * - URL parameter management
 * - Active filter display
 * - Product count per filter
 */

class ProductAttributeFilter {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.options = {
            categoryId: options.categoryId || null,
            productTypeId: options.productTypeId || null,
            apiBaseUrl: options.apiBaseUrl || '/api/filters',
            productsContainerId: options.productsContainerId || 'products-container',
            filterLogic: options.filterLogic || 'and', // 'and' or 'or'
            onFilterChange: options.onFilterChange || null,
            ...options
        };

        this.filters = {};
        this.activeFilters = {};
        this.filterData = null;
        this.isLoading = false;

        this.init();
    }

    /**
     * Initialize the filter component.
     */
    async init() {
        await this.loadFilters();
        this.renderFilters();
        this.loadFiltersFromURL();
        this.attachEventListeners();
    }

    /**
     * Load available filters from API.
     */
    async loadFilters() {
        try {
            const params = new URLSearchParams();
            if (this.options.categoryId) params.append('category_id', this.options.categoryId);
            if (this.options.productTypeId) params.append('product_type_id', this.options.productTypeId);

            const response = await fetch(`${this.options.apiBaseUrl}?${params}`);
            const data = await response.json();
            
            this.filterData = data.data;
        } catch (error) {
            console.error('Error loading filters:', error);
        }
    }

    /**
     * Render filter UI.
     */
    renderFilters() {
        if (!this.filterData || this.filterData.length === 0) {
            this.container.innerHTML = '<p class="text-gray-500">No filters available</p>';
            return;
        }

        let html = '<div class="product-filters">';
        
        this.filterData.forEach(filter => {
            html += this.renderFilterGroup(filter);
        });

        html += '</div>';
        html += this.renderActiveFilters();
        
        this.container.innerHTML = html;
        this.attachFilterEventListeners();
    }

    /**
     * Render a single filter group.
     */
    renderFilterGroup(filter) {
        let html = `<div class="filter-group" data-attribute-handle="${filter.handle}">`;
        html += `<h3 class="filter-title">${filter.name}</h3>`;
        
        if (filter.is_numeric) {
            html += this.renderNumericFilter(filter);
        } else if (filter.is_color) {
            html += this.renderColorFilter(filter);
        } else if (filter.is_boolean) {
            html += this.renderBooleanFilter(filter);
        } else {
            html += this.renderSelectFilter(filter);
        }
        
        html += '</div>';
        return html;
    }

    /**
     * Render numeric filter with range slider.
     */
    renderNumericFilter(filter) {
        const min = filter.options.min || 0;
        const max = filter.options.max || 100;
        const unit = filter.unit || '';
        
        return `
            <div class="numeric-filter">
                <div class="range-inputs">
                    <input type="number" 
                           class="range-min" 
                           min="${min}" 
                           max="${max}" 
                           value="${min}"
                           data-attribute="${filter.handle}">
                    <span class="range-separator">-</span>
                    <input type="number" 
                           class="range-max" 
                           min="${min}" 
                           max="${max}" 
                           value="${max}"
                           data-attribute="${filter.handle}">
                    <span class="range-unit">${unit}</span>
                </div>
                <div class="range-slider-container">
                    <input type="range" 
                           class="range-slider" 
                           min="${min}" 
                           max="${max}" 
                           step="${(max - min) / 100}"
                           data-attribute="${filter.handle}"
                           data-min-input=".range-min"
                           data-max-input=".range-max">
                </div>
            </div>
        `;
    }

    /**
     * Render color filter with swatches.
     */
    renderColorFilter(filter) {
        let html = '<div class="color-filter">';
        filter.options.forEach(option => {
            html += `
                <label class="color-swatch ${this.isFilterActive(filter.handle, option.value) ? 'active' : ''}" 
                       data-attribute="${filter.handle}" 
                       data-value="${option.value}">
                    <input type="checkbox" 
                           value="${option.value}"
                           ${this.isFilterActive(filter.handle, option.value) ? 'checked' : ''}>
                    <span class="color-box" style="background-color: ${option.hex}"></span>
                    <span class="color-label">${option.label}</span>
                    <span class="color-count">(${option.count})</span>
                </label>
            `;
        });
        html += '</div>';
        return html;
    }

    /**
     * Render boolean filter.
     */
    renderBooleanFilter(filter) {
        let html = '<div class="boolean-filter">';
        filter.options.forEach(option => {
            html += `
                <label class="boolean-option ${this.isFilterActive(filter.handle, option.value) ? 'active' : ''}">
                    <input type="checkbox" 
                           value="${option.value}"
                           data-attribute="${filter.handle}"
                           ${this.isFilterActive(filter.handle, option.value) ? 'checked' : ''}>
                    <span>${option.label}</span>
                    <span class="option-count">(${option.count})</span>
                </label>
            `;
        });
        html += '</div>';
        return html;
    }

    /**
     * Render select/multiselect filter.
     */
    renderSelectFilter(filter) {
        let html = '<div class="select-filter">';
        filter.options.forEach(option => {
            html += `
                <label class="select-option ${this.isFilterActive(filter.handle, option.value) ? 'active' : ''}">
                    <input type="checkbox" 
                           value="${option.value}"
                           data-attribute="${filter.handle}"
                           ${this.isFilterActive(filter.handle, option.value) ? 'checked' : ''}>
                    <span>${option.label}</span>
                    <span class="option-count">(${option.count})</span>
                </label>
            `;
        });
        html += '</div>';
        return html;
    }

    /**
     * Render active filters section.
     */
    renderActiveFilters() {
        const activeCount = Object.keys(this.activeFilters).length;
        if (activeCount === 0) {
            return '<div class="active-filters" style="display: none;"></div>';
        }

        let html = '<div class="active-filters">';
        html += '<h4>Active Filters:</h4>';
        html += '<div class="active-filter-tags">';
        
        Object.entries(this.activeFilters).forEach(([handle, values]) => {
            const filter = this.filterData.find(f => f.handle === handle);
            const filterName = filter ? filter.name : handle;
            
            if (Array.isArray(values)) {
                values.forEach(value => {
                    html += this.renderActiveFilterTag(handle, value, filterName);
                });
            } else {
                html += this.renderActiveFilterTag(handle, values, filterName);
            }
        });
        
        html += '</div>';
        html += '<button class="clear-all-filters">Clear All</button>';
        html += '</div>';
        
        return html;
    }

    /**
     * Render active filter tag.
     */
    renderActiveFilterTag(handle, value, filterName) {
        let displayValue = value;
        
        // Format display value
        if (typeof value === 'object' && value.min !== undefined && value.max !== undefined) {
            displayValue = `${value.min} - ${value.max}`;
        }
        
        return `
            <span class="active-filter-tag">
                <span class="filter-name">${filterName}:</span>
                <span class="filter-value">${displayValue}</span>
                <button class="remove-filter" data-attribute="${handle}" data-value="${JSON.stringify(value)}">×</button>
            </span>
        `;
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Clear all filters
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('clear-all-filters')) {
                this.clearAllFilters();
            }
            
            if (e.target.classList.contains('remove-filter')) {
                const handle = e.target.dataset.attribute;
                const value = JSON.parse(e.target.dataset.value);
                this.removeFilter(handle, value);
            }
        });
    }

    /**
     * Attach filter-specific event listeners.
     */
    attachFilterEventListeners() {
        // Checkbox filters
        this.container.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const handle = e.target.dataset.attribute;
                const value = e.target.value;
                
                if (e.target.checked) {
                    this.addFilter(handle, value);
                } else {
                    this.removeFilter(handle, value);
                }
            });
        });

        // Range sliders
        this.container.querySelectorAll('.range-slider').forEach(slider => {
            slider.addEventListener('input', (e) => {
                const handle = e.target.dataset.attribute;
                const minInput = this.container.querySelector(`${e.target.dataset.minInput}[data-attribute="${handle}"]`);
                const maxInput = this.container.querySelector(`${e.target.dataset.maxInput}[data-attribute="${handle}"]`);
                
                // Update range inputs
                if (minInput && maxInput) {
                    const min = parseFloat(minInput.value);
                    const max = parseFloat(maxInput.value);
                    this.updateNumericFilter(handle, { min, max });
                }
            });
        });

        // Numeric inputs
        this.container.querySelectorAll('.range-min, .range-max').forEach(input => {
            input.addEventListener('change', (e) => {
                const handle = e.target.dataset.attribute;
                const minInput = this.container.querySelector(`.range-min[data-attribute="${handle}"]`);
                const maxInput = this.container.querySelector(`.range-max[data-attribute="${handle}"]`);
                
                if (minInput && maxInput) {
                    const min = parseFloat(minInput.value);
                    const max = parseFloat(maxInput.value);
                    this.updateNumericFilter(handle, { min, max });
                }
            });
        });
    }

    /**
     * Add a filter.
     */
    addFilter(handle, value) {
        if (!this.activeFilters[handle]) {
            this.activeFilters[handle] = [];
        }
        
        if (!this.activeFilters[handle].includes(value)) {
            this.activeFilters[handle].push(value);
        }
        
        this.applyFilters();
    }

    /**
     * Remove a filter.
     */
    removeFilter(handle, value) {
        if (!this.activeFilters[handle]) {
            return;
        }
        
        if (Array.isArray(this.activeFilters[handle])) {
            this.activeFilters[handle] = this.activeFilters[handle].filter(v => 
                JSON.stringify(v) !== JSON.stringify(value)
            );
            
            if (this.activeFilters[handle].length === 0) {
                delete this.activeFilters[handle];
            }
        } else {
            delete this.activeFilters[handle];
        }
        
        this.applyFilters();
    }

    /**
     * Update numeric filter.
     */
    updateNumericFilter(handle, range) {
        this.activeFilters[handle] = range;
        this.applyFilters();
    }

    /**
     * Clear all filters.
     */
    clearAllFilters() {
        this.activeFilters = {};
        this.applyFilters();
    }

    /**
     * Check if a filter is active.
     */
    isFilterActive(handle, value) {
        if (!this.activeFilters[handle]) {
            return false;
        }
        
        if (Array.isArray(this.activeFilters[handle])) {
            return this.activeFilters[handle].includes(value);
        }
        
        return this.activeFilters[handle] === value;
    }

    /**
     * Apply filters and update products.
     */
    async applyFilters() {
        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        this.updateURL();
        this.renderActiveFilters();

        try {
            const response = await fetch(`${this.options.apiBaseUrl}/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    filters: this.activeFilters,
                    category_id: this.options.categoryId,
                    product_type_id: this.options.productTypeId,
                    logic: this.options.filterLogic,
                })
            });

            const data = await response.json();
            
            if (this.options.onFilterChange) {
                this.options.onFilterChange(data);
            } else {
                this.updateProductsContainer(data);
            }
        } catch (error) {
            console.error('Error applying filters:', error);
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Update products container.
     */
    updateProductsContainer(data) {
        const container = document.getElementById(this.options.productsContainerId);
        if (!container) {
            return;
        }

        // This would typically render products using a template
        // For now, just log the data
        console.log('Filtered products:', data);
    }

    /**
     * Update URL with filter parameters.
     */
    updateURL() {
        const url = new URL(window.location);
        
        // Remove existing filter params
        url.searchParams.delete('filters');
        
        // Add new filter params
        if (Object.keys(this.activeFilters).length > 0) {
            url.searchParams.set('filters', JSON.stringify(this.activeFilters));
        }
        
        // Update URL without reload
        window.history.pushState({}, '', url);
    }

    /**
     * Load filters from URL parameters.
     */
    loadFiltersFromURL() {
        const url = new URL(window.location);
        const filtersParam = url.searchParams.get('filters');
        
        if (filtersParam) {
            try {
                this.activeFilters = JSON.parse(filtersParam);
                this.applyFilters();
            } catch (error) {
                console.error('Error parsing filters from URL:', error);
            }
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductAttributeFilter;
}


