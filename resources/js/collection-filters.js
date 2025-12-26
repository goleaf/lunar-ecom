/**
 * Collection Filters and Sorting with AJAX
 */

(function() {
    'use strict';

    class CollectionFilters {
        constructor() {
            const configEl = document.getElementById('collection-filters-config');
            this.collectionId = configEl?.dataset?.collectionId;
            this.baseUrl = configEl?.dataset?.baseUrl;
            this.initialFilterOptions = (() => {
                try {
                    return JSON.parse(configEl?.dataset?.filterOptions || '[]');
                } catch (_) {
                    return [];
                }
            })();
            this.currentPage = 1;
            this.filters = {};
            this.sortBy = 'default';
            this.debounceTimer = null;
            this.isLoading = false;

            this.init();
        }

        init() {
            // Initialize from URL parameters
            this.loadFiltersFromURL();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Initialize filter options
            this.loadFilterOptions();
        }

        setupEventListeners() {
            // Form inputs
            const form = document.getElementById('filter-form');
            if (form) {
                form.addEventListener('change', (e) => {
                    this.handleFilterChange(e.target);
                });

                form.addEventListener('input', (e) => {
                    if (e.target.type === 'text' || e.target.type === 'number') {
                        this.debounce(() => {
                            this.handleFilterChange(e.target);
                        }, 500);
                    }
                });
            }

            // Sort dropdown
            const sortSelect = document.getElementById('sort-by');
            if (sortSelect) {
                sortSelect.addEventListener('change', (e) => {
                    this.sortBy = e.target.value;
                    this.applyFilters();
                });
            }

            // Clear filters button
            const clearBtn = document.querySelector('[onclick="clearAllFilters()"]');
            if (clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.clearAllFilters();
                });
            }
        }

        loadFiltersFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Load filters from URL
            urlParams.forEach((value, key) => {
                if (key === 'sort_by') {
                    this.sortBy = value;
                    const sortSelect = document.getElementById('sort-by');
                    if (sortSelect) {
                        sortSelect.value = value;
                    }
                } else if (key === 'page') {
                    this.currentPage = parseInt(value) || 1;
                } else {
                    this.filters[key] = value;
                    this.setFormValue(key, value);
                }
            });

            this.updateActiveFilters();
        }

        setFormValue(name, value) {
            const input = document.querySelector(`[name="${name}"]`);
            if (!input) return;

            if (input.type === 'checkbox' || input.type === 'radio') {
                if (input.value === value || (input.type === 'checkbox' && value.includes(input.value))) {
                    input.checked = true;
                }
            } else {
                input.value = value;
            }
        }

        handleFilterChange(input) {
            const name = input.name;
            const value = input.value;
            const type = input.type;

            if (type === 'checkbox') {
                this.handleCheckboxFilter(name, value, input.checked);
            } else if (type === 'radio') {
                if (input.checked) {
                    this.filters[name] = value;
                } else {
                    delete this.filters[name];
                }
            } else {
                if (value) {
                    this.filters[name] = value;
                } else {
                    delete this.filters[name];
                }
            }

            this.applyFilters();
        }

        handleCheckboxFilter(name, value, checked) {
            if (!this.filters[name]) {
                this.filters[name] = [];
            }

            if (!Array.isArray(this.filters[name])) {
                this.filters[name] = [this.filters[name]];
            }

            if (checked) {
                if (!this.filters[name].includes(value)) {
                    this.filters[name].push(value);
                }
            } else {
                this.filters[name] = this.filters[name].filter(v => v !== value);
            }

            if (this.filters[name].length === 0) {
                delete this.filters[name];
            }
        }

        applyFilters() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.currentPage = 1; // Reset to first page on filter change
            this.showLoading();

            const params = new URLSearchParams({
                ...this.filters,
                sort_by: this.sortBy,
                page: this.currentPage,
            });

            // Handle array filters
            Object.keys(this.filters).forEach(key => {
                if (Array.isArray(this.filters[key])) {
                    params.delete(key);
                    this.filters[key].forEach(val => {
                        params.append(key + '[]', val);
                    });
                }
            });

            // Update URL without reload
            const newUrl = this.baseUrl + '?' + params.toString();
            window.history.pushState({}, '', newUrl);

            // Fetch filtered products
            fetch(this.baseUrl + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                this.updateProducts(data);
                this.updateFilterOptions(data.filter_options);
                this.updateActiveFilters();
                this.hideLoading();
            })
            .catch(error => {
                console.error('Error loading products:', error);
                this.hideLoading();
            })
            .finally(() => {
                this.isLoading = false;
            });
        }

        updateProducts(data) {
            const container = document.getElementById('products-container');
            const pagination = document.getElementById('pagination-container');
            const resultsCount = document.getElementById('results-count');

            if (container) {
                container.innerHTML = data.html || '';
            }

            if (pagination && data.pagination) {
                pagination.innerHTML = this.generatePagination(data.pagination);
            }

            if (resultsCount && data.pagination) {
                const showing = data.pagination.per_page * (data.pagination.current_page - 1) + data.products.length;
                resultsCount.textContent = `Showing ${showing} of ${data.pagination.total} products`;
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        generatePagination(pagination) {
            if (pagination.last_page <= 1) return '';

            let html = '<div class="flex justify-center items-center gap-2">';

            // Previous button
            if (pagination.current_page > 1) {
                html += `<button onclick="collectionFilters.goToPage(${pagination.current_page - 1})" class="px-4 py-2 border rounded hover:bg-gray-50">Previous</button>`;
            }

            // Page numbers
            for (let i = 1; i <= pagination.last_page; i++) {
                if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                    const active = i === pagination.current_page ? 'bg-blue-600 text-white' : 'border hover:bg-gray-50';
                    html += `<button onclick="collectionFilters.goToPage(${i})" class="px-4 py-2 rounded ${active}">${i}</button>`;
                } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                    html += `<span class="px-2">...</span>`;
                }
            }

            // Next button
            if (pagination.current_page < pagination.last_page) {
                html += `<button onclick="collectionFilters.goToPage(${pagination.current_page + 1})" class="px-4 py-2 border rounded hover:bg-gray-50">Next</button>`;
            }

            html += '</div>';
            return html;
        }

        goToPage(page) {
            this.currentPage = page;
            this.applyFilters();
        }

        updateFilterOptions(filterOptions) {
            if (!filterOptions) return;

            // Update price range display
            if (filterOptions.price_range) {
                const display = document.getElementById('price-range-display');
                if (display) {
                    display.textContent = `$${filterOptions.price_range.min.toFixed(2)} - $${filterOptions.price_range.max.toFixed(2)}`;
                }
            }

            // Update availability counts
            if (filterOptions.availability) {
                Object.keys(filterOptions.availability).forEach(key => {
                    const countEl = document.getElementById(`availability-${key}-count`);
                    if (countEl) {
                        countEl.textContent = `(${filterOptions.availability[key]})`;
                    }
                });
            }

            // Update brands
            if (filterOptions.brands && filterOptions.brands.length > 0) {
                this.updateBrandsFilter(filterOptions.brands);
            }

            // Update categories
            if (filterOptions.categories && filterOptions.categories.length > 0) {
                this.updateCategoriesFilter(filterOptions.categories);
            }

            // Update attributes
            if (filterOptions.attributes && filterOptions.attributes.length > 0) {
                this.updateAttributesFilter(filterOptions.attributes);
            }
        }

        updateBrandsFilter(brands) {
            const container = document.getElementById('brands-list');
            const section = document.getElementById('brands-filter');
            
            if (!container) return;

            if (brands.length === 0) {
                section.style.display = 'none';
                return;
            }

            section.style.display = 'block';
            container.innerHTML = brands.map(brand => `
                <label class="flex items-center">
                    <input type="checkbox" name="brands[]" value="${brand.id}" class="mr-2">
                    <span class="text-sm flex-1">${brand.name}</span>
                    <span class="text-sm text-gray-500">(${brand.count})</span>
                </label>
            `).join('');

            // Re-attach event listeners
            container.querySelectorAll('input').forEach(input => {
                input.addEventListener('change', (e) => {
                    this.handleFilterChange(e.target);
                });
            });
        }

        updateCategoriesFilter(categories) {
            const container = document.getElementById('categories-list');
            const section = document.getElementById('categories-filter');
            
            if (!container) return;

            if (categories.length === 0) {
                section.style.display = 'none';
                return;
            }

            section.style.display = 'block';
            container.innerHTML = categories.map(category => `
                <label class="flex items-center">
                    <input type="checkbox" name="categories[]" value="${category.id}" class="mr-2">
                    <span class="text-sm flex-1">${category.name}</span>
                    <span class="text-sm text-gray-500">(${category.count})</span>
                </label>
            `).join('');

            // Re-attach event listeners
            container.querySelectorAll('input').forEach(input => {
                input.addEventListener('change', (e) => {
                    this.handleFilterChange(e.target);
                });
            });
        }

        updateAttributesFilter(attributes) {
            const container = document.getElementById('attributes-filter');
            if (!container) return;

            container.innerHTML = attributes.map(attr => `
                <div class="filter-section attribute-filter">
                    <h4>${attr.name}</h4>
                    <div class="attribute-values">
                        ${attr.values.map(value => `
                            <label class="attribute-value-item">
                                <input type="checkbox" 
                                       name="attributes[${attr.handle}][]" 
                                       value="${value.id}" 
                                       class="mr-2">
                                <span class="text-sm flex-1">${value.name}</span>
                                <span class="text-sm text-gray-500">(${value.count})</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `).join('');

            // Re-attach event listeners
            container.querySelectorAll('input').forEach(input => {
                input.addEventListener('change', (e) => {
                    this.handleFilterChange(e.target);
                });
            });
        }

        updateActiveFilters() {
            const container = document.getElementById('active-filters');
            if (!container) return;

            const activeFilters = [];

            // Price range
            if (this.filters.min_price || this.filters.max_price) {
                activeFilters.push({
                    label: `Price: $${this.filters.min_price || '0'} - $${this.filters.max_price || '∞'}`,
                    key: 'price',
                });
            }

            // Availability
            if (this.filters.availability) {
                const labels = {
                    'in_stock': 'In Stock',
                    'low_stock': 'Low Stock',
                    'out_of_stock': 'Out of Stock',
                };
                activeFilters.push({
                    label: labels[this.filters.availability] || this.filters.availability,
                    key: 'availability',
                });
            }

            // Brands
            if (this.filters.brands && Array.isArray(this.filters.brands)) {
                this.filters.brands.forEach(brandId => {
                    activeFilters.push({
                        label: `Brand: ${brandId}`,
                        key: 'brands',
                        value: brandId,
                    });
                });
            }

            // Categories
            if (this.filters.categories && Array.isArray(this.filters.categories)) {
                this.filters.categories.forEach(categoryId => {
                    activeFilters.push({
                        label: `Category: ${categoryId}`,
                        key: 'categories',
                        value: categoryId,
                    });
                });
            }

            // Attributes
            if (this.filters.attributes) {
                Object.keys(this.filters.attributes).forEach(handle => {
                    if (Array.isArray(this.filters.attributes[handle])) {
                        this.filters.attributes[handle].forEach(valueId => {
                            activeFilters.push({
                                label: `${handle}: ${valueId}`,
                                key: 'attributes',
                                handle: handle,
                                value: valueId,
                            });
                        });
                    }
                });
            }

            // Rating
            if (this.filters.min_rating) {
                activeFilters.push({
                    label: `Rating: ${this.filters.min_rating}+ ⭐`,
                    key: 'min_rating',
                });
            }

            // Search
            if (this.filters.search) {
                activeFilters.push({
                    label: `Search: "${this.filters.search}"`,
                    key: 'search',
                });
            }

            container.innerHTML = activeFilters.map(filter => `
                <span class="active-filter">
                    ${filter.label}
                    <button onclick="collectionFilters.removeFilter('${filter.key}', '${filter.value || ''}', '${filter.handle || ''}')" 
                            aria-label="Remove filter">
                        ×
                    </button>
                </span>
            `).join('');
        }

        removeFilter(key, value, handle) {
            if (key === 'price') {
                delete this.filters.min_price;
                delete this.filters.max_price;
                document.getElementById('min_price').value = '';
                document.getElementById('max_price').value = '';
            } else if (Array.isArray(this.filters[key])) {
                this.filters[key] = this.filters[key].filter(v => v !== value);
                if (this.filters[key].length === 0) {
                    delete this.filters[key];
                }
            } else if (handle && this.filters.attributes && this.filters.attributes[handle]) {
                this.filters.attributes[handle] = this.filters.attributes[handle].filter(v => v !== value);
                if (this.filters.attributes[handle].length === 0) {
                    delete this.filters.attributes[handle];
                }
                if (Object.keys(this.filters.attributes).length === 0) {
                    delete this.filters.attributes;
                }
            } else {
                delete this.filters[key];
            }

            this.applyFilters();
        }

        clearAllFilters() {
            this.filters = {};
            this.sortBy = 'default';
            this.currentPage = 1;

            // Clear form
            const form = document.getElementById('filter-form');
            if (form) {
                form.reset();
            }

            const sortSelect = document.getElementById('sort-by');
            if (sortSelect) {
                sortSelect.value = 'default';
            }

            this.applyFilters();
        }

        loadFilterOptions() {
            // Initial load of filter options
            if (this.initialFilterOptions) {
                this.updateFilterOptions(this.initialFilterOptions);
            }
        }

        showLoading() {
            const indicator = document.getElementById('loading-indicator');
            const container = document.getElementById('products-container');
            
            if (indicator) {
                indicator.classList.remove('hidden');
            }
            if (container) {
                container.classList.add('loading');
            }
        }

        hideLoading() {
            const indicator = document.getElementById('loading-indicator');
            const container = document.getElementById('products-container');
            
            if (indicator) {
                indicator.classList.add('hidden');
            }
            if (container) {
                container.classList.remove('loading');
            }
        }

        debounce(func, wait) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(func, wait);
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const configEl = document.getElementById('collection-filters-config');
        if (!configEl) return;
        window.collectionFilters = new CollectionFilters();
    });

    // Make clearAllFilters available globally
    window.clearAllFilters = function() {
        if (window.collectionFilters) {
            window.collectionFilters.clearAllFilters();
        }
    };
})();

