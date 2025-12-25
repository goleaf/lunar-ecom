{{-- Product Attribute Filters Component --}}
<div id="product-filters-container"></div>
<div id="products-container"></div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/product-filters.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/components/ProductAttributeFilter.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filter = new ProductAttributeFilter('product-filters-container', {
                categoryId: {{ $category->id ?? 'null' }},
                productTypeId: {{ $productTypeId ?? 'null' }},
                apiBaseUrl: '{{ url('/api/filters') }}',
                productsContainerId: 'products-container',
                filterLogic: 'and',
                onFilterChange: function(data) {
                    // Update products display
                    updateProductsDisplay(data);
                }
            });

            function updateProductsDisplay(data) {
                const container = document.getElementById('products-container');
                if (!container) return;

                // Render products (implement your product card template here)
                let html = '<div class="products-grid">';
                
                data.data.forEach(product => {
                    html += `
                        <div class="product-card">
                            <h3>${product.name || 'Product'}</h3>
                            <!-- Add more product details -->
                        </div>
                    `;
                });
                
                html += '</div>';
                
                // Add pagination
                if (data.meta.last_page > 1) {
                    html += renderPagination(data.meta);
                }
                
                container.innerHTML = html;
            }

            function renderPagination(meta) {
                let html = '<div class="pagination">';
                for (let i = 1; i <= meta.last_page; i++) {
                    html += `<a href="?page=${i}" class="page-link ${i === meta.current_page ? 'active' : ''}">${i}</a>`;
                }
                html += '</div>';
                return html;
            }
        });
    </script>
@endpush

