/**
 * Comparison Page Component
 * 
 * Side-by-side product comparison display.
 */

class ComparisonPage {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.options = {
            apiUrl: options.apiUrl || '/comparison',
            onAttributeChange: options.onAttributeChange || null,
            ...options
        };

        this.comparisonTable = null;
        this.selectedAttributes = [];
        this.init();
    }

    /**
     * Initialize the component.
     */
    async init() {
        await this.loadComparison();
        this.render();
        this.attachEventListeners();
    }

    /**
     * Load comparison data.
     */
    async loadComparison() {
        try {
            const url = new URL(`${this.options.apiUrl}`, window.location.origin);
            if (this.selectedAttributes.length > 0) {
                url.searchParams.set('attributes', JSON.stringify(this.selectedAttributes));
            }

            const response = await fetch(url);
            const data = await response.json();
            this.comparisonTable = data;
        } catch (error) {
            console.error('Error loading comparison:', error);
        }
    }

    /**
     * Render the component.
     */
    render() {
        if (!this.comparisonTable || !this.comparisonTable.products || this.comparisonTable.products.length === 0) {
            this.container.innerHTML = '<div class="empty-comparison"><p>No products to compare. Add products to comparison to see them here.</p></div>';
            return;
        }

        let html = '<div class="comparison-page">';
        
        // Header with actions
        html += '<div class="comparison-header">';
        html += '<h1>Product Comparison</h1>';
        html += '<div class="comparison-actions">';
        html += '<button class="btn-export-pdf" id="export-pdf">Export as PDF</button>';
        html += '<button class="btn-clear" id="clear-comparison">Clear Comparison</button>';
        html += '</div>';
        html += '</div>';

        // Attribute selector
        html += this.renderAttributeSelector();

        // Comparison table
        html += '<div class="comparison-table-container">';
        html += '<table class="comparison-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th class="attribute-col">Attribute</th>';
        this.comparisonTable.products.forEach(product => {
            html += `<th class="product-col" data-product-id="${product.id}">`;
            html += `<div class="product-header">`;
            html += `<img src="${product.image || '/placeholder.jpg'}" alt="${this.escapeHtml(product.name)}" class="product-image">`;
            html += `<h3>${this.escapeHtml(product.name)}</h3>`;
            html += `<div class="product-price">$${(product.price / 100).toFixed(2)}</div>`;
            html += `<div class="product-rating">${this.renderStars(product.rating)} (${product.total_reviews})</div>`;
            html += `<button class="btn-add-to-cart" data-product-id="${product.id}">Add to Cart</button>`;
            html += `<button class="btn-remove" data-product-id="${product.id}">Remove</button>`;
            html += `</div>`;
            html += `</th>`;
        });
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        // Basic info row
        html += '<tr class="info-row">';
        html += '<td><strong>SKU</strong></td>';
        this.comparisonTable.products.forEach(product => {
            html += `<td>${this.escapeHtml(product.sku || '—')}</td>`;
        });
        html += '</tr>';

        html += '<tr class="info-row">';
        html += '<td><strong>Brand</strong></td>';
        this.comparisonTable.products.forEach(product => {
            html += `<td>${this.escapeHtml(product.brand || '—')}</td>`;
        });
        html += '</tr>';

        html += '<tr class="info-row">';
        html += '<td><strong>In Stock</strong></td>';
        this.comparisonTable.products.forEach(product => {
            html += `<td>${product.in_stock ? '<span class="in-stock">✓ In Stock</span>' : '<span class="out-of-stock">✗ Out of Stock</span>'}</td>`;
        });
        html += '</tr>';

        // Attribute rows
        this.comparisonTable.attributes.forEach(attr => {
            html += '<tr class="attribute-row">';
            html += `<td><strong>${this.escapeHtml(attr.attribute_name)}</strong></td>`;
            attr.values.forEach(value => {
                html += `<td>${this.escapeHtml(value)}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody>';
        html += '</table>';
        html += '</div>';

        html += '</div>';

        this.container.innerHTML = html;
        this.highlightDifferences();
    }

    /**
     * Render attribute selector.
     */
    renderAttributeSelector() {
        // This would be populated from API
        return `
            <div class="attribute-selector">
                <h3>Select Attributes to Compare</h3>
                <div class="attribute-checkboxes" id="attribute-checkboxes">
                    <!-- Populated dynamically -->
                </div>
            </div>
        `;
    }

    /**
     * Render star rating.
     */
    renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

        let html = '';
        for (let i = 0; i < fullStars; i++) {
            html += '★';
        }
        if (hasHalfStar) {
            html += '½';
        }
        for (let i = 0; i < emptyStars; i++) {
            html += '☆';
        }

        return html;
    }

    /**
     * Highlight differences between products.
     */
    highlightDifferences() {
        const rows = this.container.querySelectorAll('.attribute-row');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td:not(:first-child)');
            const values = Array.from(cells).map(cell => cell.textContent.trim());
            const uniqueValues = [...new Set(values)];

            if (uniqueValues.length > 1) {
                row.classList.add('has-difference');
                cells.forEach((cell, index) => {
                    if (values[index] !== values[0]) {
                        cell.classList.add('different-value');
                    }
                });
            }
        });
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Export PDF
        const exportBtn = this.container.querySelector('#export-pdf');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                window.location.href = `${this.options.apiUrl}/export-pdf`;
            });
        }

        // Clear comparison
        const clearBtn = this.container.querySelector('#clear-comparison');
        if (clearBtn) {
            clearBtn.addEventListener('click', async () => {
                if (confirm('Clear all products from comparison?')) {
                    await fetch(`${this.options.apiUrl}/clear`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    window.location.reload();
                }
            });
        }

        // Remove product
        this.container.addEventListener('click', async (e) => {
            if (e.target.classList.contains('btn-remove')) {
                const productId = e.target.dataset.productId;
                await fetch(`${this.options.apiUrl}/remove`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ product_id: productId }),
                });
                await this.loadComparison();
                this.render();
            }
        });

        // Add to cart
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-add-to-cart')) {
                const productId = e.target.dataset.productId;
                // Implement add to cart logic
                console.log('Add to cart:', productId);
            }
        });
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
    module.exports = ComparisonPage;
}

