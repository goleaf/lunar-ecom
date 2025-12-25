/**
 * Bundle Display Component
 * 
 * Frontend component for displaying bundles with savings.
 */

class BundleDisplay {
    constructor(containerId, bundleId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.bundleId = bundleId;
        this.options = {
            apiUrl: options.apiUrl || `/bundles/${bundleId}`,
            onAddToCart: options.onAddToCart || null,
            ...options
        };

        this.selectedItems = [];
        this.pricing = null;
        this.init();
    }

    /**
     * Initialize the component.
     */
    async init() {
        await this.loadBundle();
        this.render();
        this.attachEventListeners();
    }

    /**
     * Load bundle data.
     */
    async loadBundle() {
        try {
            const response = await fetch(this.options.apiUrl);
            const data = await response.json();
            this.bundle = data.bundle;
            this.pricing = data.pricing;
            this.availability = data.availability;
            this.availableProducts = data.available_products || [];
        } catch (error) {
            console.error('Error loading bundle:', error);
        }
    }

    /**
     * Render the component.
     */
    render() {
        if (!this.bundle) {
            this.container.innerHTML = '<div class="error">Bundle not found</div>';
            return;
        }

        let html = '<div class="bundle-display">';
        
        // Bundle header
        html += '<div class="bundle-header">';
        html += `<h1>${this.escapeHtml(this.bundle.product.name)}</h1>`;
        if (this.pricing && this.bundle.show_savings) {
            html += this.renderSavings();
        }
        html += '</div>';

        // Bundle items
        html += '<div class="bundle-items">';
        if (this.bundle.bundle_type === 'fixed') {
            html += this.renderFixedBundleItems();
        } else {
            html += this.renderDynamicBundleItems();
        }
        html += '</div>';

        // Pricing summary
        if (this.pricing && this.bundle.show_individual_prices) {
            html += this.renderPricingSummary();
        }

        // Add to cart
        html += this.renderAddToCart();

        html += '</div>';

        this.container.innerHTML = html;
    }

    /**
     * Render savings badge.
     */
    renderSavings() {
        if (!this.pricing || this.pricing.savings_amount <= 0) {
            return '';
        }

        const savingsText = this.bundle.discount_type === 'percentage'
            ? `${this.pricing.savings_percentage}% OFF`
            : `Save $${(this.pricing.savings_amount / 100).toFixed(2)}`;

        return `<div class="savings-badge">${savingsText}</div>`;
    }

    /**
     * Render fixed bundle items.
     */
    renderFixedBundleItems() {
        let html = '<h3>Bundle Includes:</h3><ul class="bundle-items-list">';
        
        this.pricing.items.forEach(item => {
            html += `
                <li class="bundle-item">
                    <span class="item-name">${this.escapeHtml(item.product_name)}</span>
                    <span class="item-quantity">× ${item.quantity}</span>
                    ${this.bundle.show_individual_prices ? `<span class="item-price">$${(item.total_price / 100).toFixed(2)}</span>` : ''}
                </li>
            `;
        });
        
        html += '</ul>';
        return html;
    }

    /**
     * Render dynamic bundle builder.
     */
    renderDynamicBundleItems() {
        let html = '<h3>Build Your Bundle:</h3>';
        html += `<p>Select ${this.bundle.min_items || 1} to ${this.bundle.max_items || 'unlimited'} items</p>`;
        html += '<div class="available-products-grid">';

        this.availableProducts.forEach(product => {
            html += `
                <div class="product-card" data-product-id="${product.id}">
                    <h4>${this.escapeHtml(product.name)}</h4>
                    <div class="variants">
                        ${product.variants.map(variant => `
                            <label>
                                <input type="checkbox" 
                                       class="variant-select" 
                                       data-product-id="${product.id}"
                                       data-variant-id="${variant.id}"
                                       data-price="${variant.price}">
                                ${variant.sku} - $${(variant.price / 100).toFixed(2)}
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
        });

        html += '</div>';
        html += '<div class="selected-items" id="selected-items"></div>';

        return html;
    }

    /**
     * Render pricing summary.
     */
    renderPricingSummary() {
        if (!this.pricing) return '';

        return `
            <div class="pricing-summary">
                <div class="price-row">
                    <span>Original Price:</span>
                    <span class="original-price">$${(this.pricing.original_price / 100).toFixed(2)}</span>
                </div>
                <div class="price-row">
                    <span>Discount:</span>
                    <span class="discount">-$${(this.pricing.discount_amount / 100).toFixed(2)}</span>
                </div>
                <div class="price-row total">
                    <span>Bundle Price:</span>
                    <span class="bundle-price">$${(this.pricing.bundle_price / 100).toFixed(2)}</span>
                </div>
            </div>
        `;
    }

    /**
     * Render add to cart button.
     */
    renderAddToCart() {
        const isAvailable = this.availability?.is_available !== false;
        
        return `
            <div class="add-to-cart-section">
                <button class="add-to-cart-btn ${!isAvailable ? 'disabled' : ''}" 
                        ${!isAvailable ? 'disabled' : ''}>
                    Add Bundle to Cart
                </button>
                ${!isAvailable ? '<p class="unavailable-message">Some items are out of stock</p>' : ''}
            </div>
        `;
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Dynamic bundle item selection
        this.container.addEventListener('change', async (e) => {
            if (e.target.classList.contains('variant-select')) {
                await this.updateSelectedItems();
                await this.updatePricing();
            }
        });

        // Add to cart
        this.container.addEventListener('click', async (e) => {
            if (e.target.classList.contains('add-to-cart-btn') && !e.target.disabled) {
                await this.addToCart();
            }
        });
    }

    /**
     * Update selected items from checkboxes.
     */
    async updateSelectedItems() {
        const checkboxes = this.container.querySelectorAll('.variant-select:checked');
        this.selectedItems = Array.from(checkboxes).map(cb => ({
            product_id: parseInt(cb.dataset.productId),
            product_variant_id: parseInt(cb.dataset.variantId),
            quantity: 1,
        }));

        // Update selected items display
        const selectedContainer = this.container.querySelector('#selected-items');
        if (selectedContainer) {
            selectedContainer.innerHTML = `
                <h4>Selected Items (${this.selectedItems.length})</h4>
                <ul>
                    ${this.selectedItems.map(item => `<li>Item ${item.product_id}</li>`).join('')}
                </ul>
            `;
        }
    }

    /**
     * Update pricing via AJAX.
     */
    async updatePricing() {
        try {
            const response = await fetch(`${this.options.apiUrl}/calculate-price`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    selected_items: this.selectedItems,
                }),
            });

            const data = await response.json();
            this.pricing = data;
            this.render();
        } catch (error) {
            console.error('Error updating pricing:', error);
        }
    }

    /**
     * Add bundle to cart.
     */
    async addToCart() {
        try {
            const response = await fetch(`${this.options.apiUrl}/add-to-cart`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    quantity: 1,
                    selected_items: this.bundle.bundle_type === 'dynamic' ? this.selectedItems : null,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                if (this.options.onAddToCart) {
                    this.options.onAddToCart(data);
                } else {
                    alert('Bundle added to cart!');
                }
            } else {
                alert(data.message || 'Failed to add bundle to cart');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            alert('An error occurred. Please try again.');
        }
    }

    /**
     * Escape HTML.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BundleDisplay;
}

