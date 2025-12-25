/**
 * Bundle Builder Component
 * 
 * Admin interface for building product bundles with drag-drop.
 */

class BundleBuilder {
    constructor(containerId, bundleId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.bundleId = bundleId;
        this.options = {
            apiUrl: options.apiUrl || `/admin/bundles/${bundleId}`,
            onSave: options.onSave || null,
            ...options
        };

        this.items = [];
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
            this.bundle = data;
            this.items = data.items || [];
        } catch (error) {
            console.error('Error loading bundle:', error);
        }
    }

    /**
     * Render the component.
     */
    render() {
        let html = '<div class="bundle-builder">';
        html += '<div class="bundle-config">';
        html += this.renderBundleConfig();
        html += '</div>';
        html += '<div class="bundle-items-section">';
        html += '<h3>Bundle Items</h3>';
        html += '<div class="available-products" id="available-products"></div>';
        html += '<div class="bundle-items" id="bundle-items"></div>';
        html += '</div>';
        html += '</div>';

        this.container.innerHTML = html;
        this.renderItems();
    }

    /**
     * Render bundle configuration.
     */
    renderBundleConfig() {
        const bundle = this.bundle || {};
        return `
            <div class="config-group">
                <label>Bundle Type</label>
                <select id="bundle-type" class="form-control">
                    <option value="fixed" ${bundle.bundle_type === 'fixed' ? 'selected' : ''}>Fixed</option>
                    <option value="dynamic" ${bundle.bundle_type === 'dynamic' ? 'selected' : ''}>Dynamic</option>
                </select>
            </div>
            <div class="config-group">
                <label>Discount Type</label>
                <select id="discount-type" class="form-control">
                    <option value="percentage" ${bundle.discount_type === 'percentage' ? 'selected' : ''}>Percentage</option>
                    <option value="fixed" ${bundle.discount_type === 'fixed' ? 'selected' : ''}>Fixed Amount</option>
                </select>
            </div>
            <div class="config-group">
                <label>Discount Value</label>
                <input type="number" id="discount-value" class="form-control" value="${bundle.discount_value || 0}" step="0.01">
            </div>
            <div class="config-group" id="dynamic-settings" style="display: ${bundle.bundle_type === 'dynamic' ? 'block' : 'none'}">
                <label>Min Items</label>
                <input type="number" id="min-items" class="form-control" value="${bundle.min_items || ''}" min="1">
                <label>Max Items</label>
                <input type="number" id="max-items" class="form-control" value="${bundle.max_items || ''}" min="1">
            </div>
        `;
    }

    /**
     * Render bundle items.
     */
    renderItems() {
        const itemsContainer = this.container.querySelector('#bundle-items');
        if (!itemsContainer) return;

        if (this.items.length === 0) {
            itemsContainer.innerHTML = '<p class="empty-state">No items in bundle. Drag products here or click "Add Product".</p>';
            return;
        }

        let html = '<div class="items-list" id="items-list">';
        this.items.forEach((item, index) => {
            html += this.renderItem(item, index);
        });
        html += '</div>';

        itemsContainer.innerHTML = html;
        this.initializeDragDrop();
    }

    /**
     * Render a single bundle item.
     */
    renderItem(item, index) {
        return `
            <div class="bundle-item" data-item-id="${item.id || index}" draggable="true">
                <div class="item-handle">☰</div>
                <div class="item-content">
                    <h4>${this.escapeHtml(item.product?.name || 'Product')}</h4>
                    <div class="item-details">
                        <span>Qty: <input type="number" value="${item.quantity}" min="1" class="item-quantity"></span>
                        <span>Optional: <input type="checkbox" ${item.is_optional ? 'checked' : ''} class="item-optional"></span>
                    </div>
                </div>
                <button class="remove-item-btn" data-item-id="${item.id || index}">×</button>
            </div>
        `;
    }

    /**
     * Initialize drag and drop.
     */
    initializeDragDrop() {
        const itemsList = this.container.querySelector('#items-list');
        if (!itemsList) return;

        itemsList.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('bundle-item')) {
                e.target.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', e.target.outerHTML);
            }
        });

        itemsList.addEventListener('dragover', (e) => {
            e.preventDefault();
            const dragging = itemsList.querySelector('.dragging');
            if (!dragging) return;

            const afterElement = this.getDragAfterElement(itemsList, e.clientY);
            if (afterElement == null) {
                itemsList.appendChild(dragging);
            } else {
                itemsList.insertBefore(dragging, afterElement);
            }
        });

        itemsList.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('bundle-item')) {
                e.target.classList.remove('dragging');
                this.updateItemOrder();
            }
        });
    }

    /**
     * Get element after drag position.
     */
    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.bundle-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    /**
     * Update item order after drag.
     */
    updateItemOrder() {
        const items = this.container.querySelectorAll('.bundle-item');
        items.forEach((item, index) => {
            const itemId = item.dataset.itemId;
            // Update display_order
            const bundleItem = this.items.find(i => (i.id || i.temp_id) == itemId);
            if (bundleItem) {
                bundleItem.display_order = index;
            }
        });
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Bundle type change
        const bundleType = this.container.querySelector('#bundle-type');
        if (bundleType) {
            bundleType.addEventListener('change', (e) => {
                const dynamicSettings = this.container.querySelector('#dynamic-settings');
                if (dynamicSettings) {
                    dynamicSettings.style.display = e.target.value === 'dynamic' ? 'block' : 'none';
                }
            });
        }

        // Remove item
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item-btn')) {
                const itemId = e.target.dataset.itemId;
                this.removeItem(itemId);
            }
        });

        // Quantity/optional changes
        this.container.addEventListener('change', (e) => {
            if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-optional')) {
                const itemId = e.target.closest('.bundle-item')?.dataset.itemId;
                if (itemId) {
                    this.updateItem(itemId, {
                        quantity: parseInt(e.target.closest('.bundle-item').querySelector('.item-quantity').value),
                        is_optional: e.target.closest('.bundle-item').querySelector('.item-optional').checked,
                    });
                }
            }
        });
    }

    /**
     * Add item to bundle.
     */
    async addItem(productId, variantId = null) {
        try {
            const response = await fetch(`${this.options.apiUrl}/items`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    product_id: productId,
                    product_variant_id: variantId,
                    quantity: 1,
                }),
            });

            const data = await response.json();
            this.items.push(data.item);
            this.renderItems();
        } catch (error) {
            console.error('Error adding item:', error);
        }
    }

    /**
     * Update item.
     */
    async updateItem(itemId, updates) {
        const item = this.items.find(i => (i.id || i.temp_id) == itemId);
        if (item) {
            Object.assign(item, updates);
        }
    }

    /**
     * Remove item.
     */
    async removeItem(itemId) {
        const item = this.items.find(i => (i.id || i.temp_id) == itemId);
        if (item && item.id) {
            try {
                await fetch(`${this.options.apiUrl}/items/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
            } catch (error) {
                console.error('Error removing item:', error);
            }
        }

        this.items = this.items.filter(i => (i.id || i.temp_id) != itemId);
        this.renderItems();
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
    module.exports = BundleBuilder;
}

