/**
 * Barcode Scanner Component
 * 
 * Quick stock updates using barcode scanning.
 */

class BarcodeScanner {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.options = {
            apiUrl: options.apiUrl || '/admin/inventory/barcode-scan',
            onScan: options.onScan || null,
            ...options
        };

        this.init();
    }

    /**
     * Initialize the component.
     */
    init() {
        this.render();
        this.attachEventListeners();
        this.setupBarcodeInput();
    }

    /**
     * Render the component.
     */
    render() {
        const html = `
            <div class="barcode-scanner">
                <div class="scanner-input-group">
                    <input type="text" 
                           id="barcode-input" 
                           class="barcode-input" 
                           placeholder="Scan or enter barcode..."
                           autocomplete="off">
                    <button type="button" class="scan-btn" id="scan-btn">Scan</button>
                </div>
                <div id="scan-results" class="scan-results"></div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    /**
     * Setup barcode input with auto-submit.
     */
    setupBarcodeInput() {
        const input = this.container.querySelector('#barcode-input');
        let lastScanTime = 0;

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleScan(input.value);
                input.value = '';
            }
        });

        // Auto-detect barcode scanner (rapid input)
        input.addEventListener('input', (e) => {
            const now = Date.now();
            const timeSinceLastChar = now - lastScanTime;
            lastScanTime = now;

            // If input is very fast (barcode scanner), auto-submit after delay
            if (timeSinceLastChar < 50 && e.target.value.length > 8) {
                clearTimeout(this.scanTimeout);
                this.scanTimeout = setTimeout(() => {
                    this.handleScan(e.target.value);
                    e.target.value = '';
                }, 100);
            }
        });

        // Focus on mount
        input.focus();
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        const scanBtn = this.container.querySelector('#scan-btn');
        const input = this.container.querySelector('#barcode-input');

        scanBtn.addEventListener('click', () => {
            if (input.value) {
                this.handleScan(input.value);
                input.value = '';
            }
        });
    }

    /**
     * Handle barcode scan.
     */
    async handleScan(barcode) {
        if (!barcode || barcode.length < 3) {
            return;
        }

        const resultsDiv = this.container.querySelector('#scan-results');
        resultsDiv.innerHTML = '<div class="scanning">Scanning...</div>';

        try {
            const response = await fetch(this.options.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ barcode }),
            });

            const data = await response.json();

            if (response.ok) {
                this.displayScanResult(data);
                if (this.options.onScan) {
                    this.options.onScan(data);
                }
            } else {
                resultsDiv.innerHTML = `<div class="scan-error">${data.message || 'Product not found'}</div>`;
            }
        } catch (error) {
            console.error('Error scanning barcode:', error);
            resultsDiv.innerHTML = '<div class="scan-error">Error scanning barcode</div>';
        }
    }

    /**
     * Display scan result.
     */
    displayScanResult(data) {
        const resultsDiv = this.container.querySelector('#scan-results');
        
        let html = '<div class="scan-result">';
        html += `<h4>${this.escapeHtml(data.product_name || 'Product')}</h4>`;
        html += `<p>SKU: ${this.escapeHtml(data.sku || 'N/A')}</p>`;
        
        if (data.inventory_levels) {
            html += '<div class="inventory-levels">';
            data.inventory_levels.forEach(level => {
                html += `
                    <div class="inventory-level-item">
                        <strong>${this.escapeHtml(level.warehouse_name)}:</strong>
                        <span>Qty: ${level.quantity}, Available: ${level.available_quantity}</span>
                    </div>
                `;
            });
            html += '</div>';
        }

        html += '</div>';
        resultsDiv.innerHTML = html;
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
    module.exports = BarcodeScanner;
}

