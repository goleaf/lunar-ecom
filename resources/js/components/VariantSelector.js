/**
 * Product Variant Selector Component
 * 
 * Real-time variant selection with availability checking and price updates.
 * 
 * Usage:
 * <VariantSelector 
 *   productId={productId}
 *   onVariantChange={(variant) => console.log(variant)}
 *   currency="USD"
 * />
 */

class VariantSelector {
    constructor(options = {}) {
        this.productId = options.productId;
        this.currency = options.currency || 'USD';
        this.selectedOptions = {};
        this.currentVariant = null;
        this.onVariantChange = options.onVariantChange || (() => {});
        this.apiBase = options.apiBase || '/api';
        
        this.init();
    }

    async init() {
        await this.loadProductOptions();
        this.render();
        this.attachEventListeners();
    }

    async loadProductOptions() {
        try {
            const response = await fetch(`${this.apiBase}/products/${this.productId}`);
            const data = await response.json();
            
            this.product = data.data;
            this.options = this.groupOptionsByType(data.data.product_options || []);
        } catch (error) {
            console.error('Error loading product options:', error);
        }
    }

    groupOptionsByType(productOptions) {
        const grouped = {};
        
        productOptions.forEach(option => {
            grouped[option.id] = {
                id: option.id,
                name: option.name,
                handle: option.handle,
                values: option.values || []
            };
        });
        
        return grouped;
    }

    render() {
        const container = document.getElementById('variant-selector');
        if (!container) return;

        container.innerHTML = '';

        // Render option selectors
        Object.values(this.options).forEach(option => {
            const optionGroup = document.createElement('div');
            optionGroup.className = 'variant-option-group';
            optionGroup.innerHTML = `
                <label class="variant-option-label">${option.name}</label>
                <div class="variant-option-values" data-option-id="${option.id}">
                    ${option.values.map(value => `
                        <button 
                            type="button"
                            class="variant-option-value ${this.isSelected(option.id, value.id) ? 'selected' : ''}"
                            data-option-id="${option.id}"
                            data-value-id="${value.id}"
                            ${this.isValueAvailable(option.id, value.id) ? '' : 'disabled'}
                        >
                            ${value.name}
                        </button>
                    `).join('')}
                </div>
            `;
            container.appendChild(optionGroup);
        });

        // Render variant info display
        const infoDisplay = document.createElement('div');
        infoDisplay.id = 'variant-info';
        infoDisplay.className = 'variant-info';
        container.appendChild(infoDisplay);

        this.updateVariantInfo();
    }

    attachEventListeners() {
        const container = document.getElementById('variant-selector');
        if (!container) return;

        container.addEventListener('click', async (e) => {
            if (e.target.classList.contains('variant-option-value')) {
                e.preventDefault();
                
                const optionId = parseInt(e.target.dataset.optionId);
                const valueId = parseInt(e.target.dataset.valueId);
                
                this.selectOption(optionId, valueId);
                await this.updateVariant();
            }
        });
    }

    selectOption(optionId, valueId) {
        this.selectedOptions[optionId] = valueId;
        this.render();
    }

    isSelected(optionId, valueId) {
        return this.selectedOptions[optionId] === valueId;
    }

    isValueAvailable(optionId, valueId) {
        // This would need to check against available combinations
        // For now, return true - implement based on your needs
        return true;
    }

    async updateVariant() {
        const optionValueIds = Object.values(this.selectedOptions);
        
        if (optionValueIds.length === 0) {
            this.currentVariant = null;
            this.updateVariantInfo();
            return;
        }

        try {
            const response = await fetch(
                `${this.apiBase}/products/${this.productId}/variants/by-options?` +
                new URLSearchParams({
                    option_values: optionValueIds
                })
            );

            if (response.ok) {
                const data = await response.json();
                this.currentVariant = data.data;
                await this.updatePrice();
                this.onVariantChange(this.currentVariant);
            } else {
                this.currentVariant = null;
            }
        } catch (error) {
            console.error('Error fetching variant:', error);
            this.currentVariant = null;
        }

        this.updateVariantInfo();
    }

    async updatePrice(quantity = 1) {
        if (!this.currentVariant) return;

        try {
            const response = await fetch(
                `${this.apiBase}/variants/${this.currentVariant.id}/price?` +
                new URLSearchParams({
                    quantity: quantity,
                    currency: this.currency
                })
            );

            if (response.ok) {
                const data = await response.json();
                this.currentPrice = data.data;
                this.updateVariantInfo();
            }
        } catch (error) {
            console.error('Error fetching price:', error);
        }
    }

    async checkAvailability() {
        if (!this.currentVariant) return;

        try {
            const response = await fetch(
                `${this.apiBase}/variants/${this.currentVariant.id}/availability`
            );

            if (response.ok) {
                const data = await response.json();
                this.availability = data.data;
                this.updateVariantInfo();
            }
        } catch (error) {
            console.error('Error checking availability:', error);
        }
    }

    updateVariantInfo() {
        const infoDisplay = document.getElementById('variant-info');
        if (!infoDisplay) return;

        if (!this.currentVariant) {
            infoDisplay.innerHTML = `
                <div class="variant-info-empty">
                    <p>Please select all options to view variant details.</p>
                </div>
            `;
            return;
        }

        const availability = this.availability || { available: false, stock: 0 };
        const price = this.currentPrice || { formatted_price: 'N/A' };

        infoDisplay.innerHTML = `
            <div class="variant-info-content">
                <div class="variant-sku">
                    <strong>SKU:</strong> ${this.currentVariant.sku || 'N/A'}
                </div>
                <div class="variant-price">
                    <span class="price-current">${price.formatted_price}</span>
                    ${price.formatted_compare_price ? `
                        <span class="price-compare">${price.formatted_compare_price}</span>
                    ` : ''}
                    ${price.savings_percentage ? `
                        <span class="price-savings">Save ${price.savings_percentage}%</span>
                    ` : ''}
                </div>
                <div class="variant-availability ${availability.available ? 'in-stock' : 'out-of-stock'}">
                    ${availability.available 
                        ? `<span class="stock-badge in-stock">In Stock (${availability.stock} available)</span>`
                        : '<span class="stock-badge out-of-stock">Out of Stock</span>'
                    }
                </div>
            </div>
        `;
    }

    getSelectedVariant() {
        return this.currentVariant;
    }

    getSelectedOptions() {
        return { ...this.selectedOptions };
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VariantSelector;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.VariantSelector = VariantSelector;
}


