/**
 * Product Management Admin UX Enhancements
 */

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    // SKU validation
    const skuInputs = document.querySelectorAll('input[name="sku"]');
    skuInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateSKU(this.value);
        });
    });

    // Price validation
    const priceInputs = document.querySelectorAll('input[type="number"][name*="price"]');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value < 0) {
                this.setCustomValidity('Price cannot be negative');
            } else {
                this.setCustomValidity('');
            }
        });
    });
});

function validateSKU(sku) {
    if (!sku) return true;
    
    // Check uniqueness via AJAX
    fetch(`/admin/api/products/validate-sku?sku=${encodeURIComponent(sku)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.valid) {
                // Show error message
                console.warn('SKU already exists');
            }
        });
}

// Drag and drop for media
function initDragDrop() {
    const dropZones = document.querySelectorAll('[x-data*="handleDrop"]');
    
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-blue-500', 'bg-blue-50');
        });
        
        zone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500', 'bg-blue-50');
        });
        
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500', 'bg-blue-50');
            
            const files = Array.from(e.dataTransfer.files);
            // Handle file upload via Livewire
            if (window.Livewire) {
                Livewire.emit('filesDropped', files);
            }
        });
    });
}

// Inline editing
function initInlineEditing() {
    const editButtons = document.querySelectorAll('[data-inline-edit]');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const cells = row.querySelectorAll('[data-editable]');
            
            cells.forEach(cell => {
                const value = cell.textContent.trim();
                const input = document.createElement('input');
                input.type = 'text';
                input.value = value;
                input.className = 'w-full border-gray-300 rounded';
                cell.innerHTML = '';
                cell.appendChild(input);
            });
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initDragDrop();
    initInlineEditing();
});

