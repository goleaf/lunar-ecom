/**
 * Product Comparison Component
 * 
 * Widget for managing product comparisons (sticky/slide-out).
 */

class ProductComparison {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.options = {
            apiUrl: options.apiUrl || '/comparison',
            maxProducts: options.maxProducts || 5,
            position: options.position || 'bottom-right', // bottom-right, bottom-left, slide-out
            ...options
        };

        this.comparisonCount = 0;
        this.init();
    }

    /**
     * Initialize the component.
     */
    async init() {
        await this.updateCount();
        this.render();
        this.attachEventListeners();
        this.startPolling();
    }

    /**
     * Update comparison count.
     */
    async updateCount() {
        try {
            const response = await fetch(`${this.options.apiUrl}/count`);
            const data = await response.json();
            this.comparisonCount = data.count || 0;
        } catch (error) {
            console.error('Error fetching comparison count:', error);
        }
    }

    /**
     * Render the component.
     */
    render() {
        const positionClass = `comparison-widget-${this.options.position}`;
        
        let html = `<div class="comparison-widget ${positionClass}" id="comparison-widget">`;
        
        if (this.options.position === 'slide-out') {
            html += '<button class="comparison-toggle" id="comparison-toggle">';
            html += `<span class="comparison-icon">⚖</span>`;
            html += `<span class="comparison-count">${this.comparisonCount}</span>`;
            html += '</button>';
            html += '<div class="comparison-panel" id="comparison-panel">';
        } else {
            html += '<div class="comparison-badge">';
        }

        html += '<div class="comparison-header">';
        html += '<h4>Compare Products</h4>';
        html += `<span class="comparison-count-badge">${this.comparisonCount}/${this.options.maxProducts}</span>`;
        html += '</div>';

        html += '<div class="comparison-products" id="comparison-products">';
        html += '<p class="empty-message">No products to compare</p>';
        html += '</div>';

        html += '<div class="comparison-actions">';
        html += '<a href="/comparison" class="btn-view-comparison">View Comparison</a>';
        html += '<button class="btn-clear-comparison" id="clear-comparison">Clear All</button>';
        html += '</div>';

        if (this.options.position === 'slide-out') {
            html += '</div>'; // Close panel
        }
        html += '</div>';

        this.container.innerHTML = html;
        
        if (this.comparisonCount > 0) {
            this.loadProducts();
        }
    }

    /**
     * Load comparison products.
     */
    async loadProducts() {
        try {
            const response = await fetch(`${this.options.apiUrl}/products`);
            const data = await response.json();
            
            const productsContainer = this.container.querySelector('#comparison-products');
            if (!productsContainer) return;

            if (data.products && data.products.length > 0) {
                let html = '';
                data.products.forEach(product => {
                    html += `
                        <div class="comparison-product-item" data-product-id="${product.id}">
                            <img src="${product.image || '/placeholder.jpg'}" alt="${this.escapeHtml(product.name)}" class="product-thumb">
                            <div class="product-info">
                                <h5>${this.escapeHtml(product.name)}</h5>
                                <p class="product-price">$${(product.price / 100).toFixed(2)}</p>
                            </div>
                            <button class="remove-product" data-product-id="${product.id}">×</button>
                        </div>
                    `;
                });
                productsContainer.innerHTML = html;
            } else {
                productsContainer.innerHTML = '<p class="empty-message">No products to compare</p>';
            }
        } catch (error) {
            console.error('Error loading comparison products:', error);
        }
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Toggle slide-out panel
        const toggle = this.container.querySelector('#comparison-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                const panel = this.container.querySelector('#comparison-panel');
                if (panel) {
                    panel.classList.toggle('open');
                }
            });
        }

        // Remove product
        this.container.addEventListener('click', async (e) => {
            if (e.target.classList.contains('remove-product') || e.target.closest('.remove-product')) {
                const productId = e.target.dataset.productId || e.target.closest('.remove-product').dataset.productId;
                await this.removeProduct(parseInt(productId));
            }
        });

        // Clear comparison
        const clearBtn = this.container.querySelector('#clear-comparison');
        if (clearBtn) {
            clearBtn.addEventListener('click', async () => {
                await this.clearComparison();
            });
        }
    }

    /**
     * Add product to comparison.
     */
    async addProduct(productId) {
        try {
            const response = await fetch(`${this.options.apiUrl}/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ product_id: productId }),
            });

            const data = await response.json();

            if (data.success) {
                await this.updateCount();
                this.render();
                this.showNotification('Product added to comparison', 'success');
            } else {
                this.showNotification(data.message || 'Failed to add product', 'error');
            }
        } catch (error) {
            console.error('Error adding product:', error);
            this.showNotification('An error occurred', 'error');
        }
    }

    /**
     * Remove product from comparison.
     */
    async removeProduct(productId) {
        try {
            const response = await fetch(`${this.options.apiUrl}/remove`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ product_id: productId }),
            });

            const data = await response.json();

            if (data.success) {
                await this.updateCount();
                this.render();
            }
        } catch (error) {
            console.error('Error removing product:', error);
        }
    }

    /**
     * Clear comparison.
     */
    async clearComparison() {
        if (!confirm('Clear all products from comparison?')) {
            return;
        }

        try {
            const response = await fetch(`${this.options.apiUrl}/clear`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                await this.updateCount();
                this.render();
                this.showNotification('Comparison cleared', 'success');
            }
        } catch (error) {
            console.error('Error clearing comparison:', error);
        }
    }

    /**
     * Start polling for comparison updates.
     */
    startPolling() {
        setInterval(() => {
            this.updateCount().then(() => {
                const currentCount = this.comparisonCount;
                const countElement = this.container.querySelector('.comparison-count');
                if (countElement && countElement.textContent != currentCount) {
                    this.render();
                }
            });
        }, 30000); // Poll every 30 seconds
    }

    /**
     * Show notification.
     */
    showNotification(message, type = 'info') {
        // Simple notification - can be enhanced
        const notification = document.createElement('div');
        notification.className = `comparison-notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
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
    module.exports = ProductComparison;
}

