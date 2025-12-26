/**
 * Comparison Checkbox Component
 * 
 * Checkbox for adding products to comparison from category/product listing pages.
 */

class ComparisonCheckbox {
    constructor(productId, options = {}) {
        this.productId = productId;
        this.options = {
            apiUrl: options.apiUrl || '/comparison',
            onAdd: options.onAdd || null,
            onRemove: options.onRemove || null,
            ...options
        };

        this.isInComparison = false;
        this.init();
    }

    /**
     * Initialize the component.
     */
    async init() {
        await this.checkStatus();
        this.render();
        this.attachEventListeners();
    }

    /**
     * Check if product is in comparison.
     */
    async checkStatus() {
        try {
            const response = await fetch(`${this.options.apiUrl}/check?product_id=${this.productId}`);
            const data = await response.json();
            this.isInComparison = data.in_comparison || false;
        } catch (error) {
            console.error('Error checking comparison status:', error);
        }
    }

    /**
     * Render the checkbox.
     */
    render() {
        const checkboxId = `compare-${this.productId}`;
        const html = `
            <label class="compare-checkbox-label" for="${checkboxId}">
                <input type="checkbox" 
                       id="${checkboxId}" 
                       class="compare-checkbox-input" 
                       ${this.isInComparison ? 'checked' : ''}
                       data-product-id="${this.productId}">
                <span class="compare-checkbox-text">Compare</span>
            </label>
        `;

        return html;
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        const checkbox = document.querySelector(`#compare-${this.productId}`);
        if (!checkbox) return;

        checkbox.addEventListener('change', async (e) => {
            if (e.target.checked) {
                await this.addToComparison();
            } else {
                await this.removeFromComparison();
            }
        });
    }

    /**
     * Add product to comparison.
     */
    async addToComparison() {
        try {
            const response = await fetch(`${this.options.apiUrl}/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ product_id: this.productId }),
            });

            const data = await response.json();

            if (data.success) {
                this.isInComparison = true;
                if (this.options.onAdd) {
                    this.options.onAdd(data);
                } else {
                    this.showNotification('Product added to comparison', 'success');
                }
            } else {
                this.showNotification(data.message || 'Failed to add product', 'error');
                // Uncheck if failed
                const checkbox = document.querySelector(`#compare-${this.productId}`);
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        } catch (error) {
            console.error('Error adding to comparison:', error);
            this.showNotification('An error occurred', 'error');
        }
    }

    /**
     * Remove product from comparison.
     */
    async removeFromComparison() {
        try {
            const response = await fetch(`${this.options.apiUrl}/remove`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ product_id: this.productId }),
            });

            const data = await response.json();

            if (data.success) {
                this.isInComparison = false;
                if (this.options.onRemove) {
                    this.options.onRemove(data);
                }
            }
        } catch (error) {
            console.error('Error removing from comparison:', error);
        }
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
}

// Auto-initialize checkboxes on page load
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.compare-checkbox-input');
    checkboxes.forEach(checkbox => {
        const productId = checkbox.dataset.productId;
        if (productId) {
            new ComparisonCheckbox(parseInt(productId));
        }
    });
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ComparisonCheckbox;
}


