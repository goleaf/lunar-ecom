/**
 * Product Recommendations Component
 * 
 * Displays product recommendations with click tracking.
 */

class ProductRecommendations {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.options = {
            productId: options.productId || null,
            algorithm: options.algorithm || 'hybrid',
            location: options.location || 'product_page',
            limit: options.limit || 10,
            apiUrl: options.apiUrl || null,
            onProductClick: options.onProductClick || null,
            ...options
        };

        this.recommendations = [];
        this.init();
    }

    /**
     * Initialize the component.
     */
    async init() {
        if (this.options.productId) {
            await this.loadRecommendations();
        }
        this.render();
        this.attachEventListeners();
    }

    /**
     * Load recommendations from API.
     */
    async loadRecommendations() {
        try {
            const apiUrl = this.options.apiUrl || 
                `/products/${this.options.productId}/recommendations?algorithm=${this.options.algorithm}&location=${this.options.location}&limit=${this.options.limit}`;

            const response = await fetch(apiUrl);
            const data = await response.json();

            this.recommendations = data.recommendations || [];
            this.render();
        } catch (error) {
            console.error('Error loading recommendations:', error);
        }
    }

    /**
     * Render the component.
     */
    render() {
        if (!this.recommendations || this.recommendations.length === 0) {
            this.container.innerHTML = '';
            return;
        }

        const title = this.getTitle();
        
        let html = `<div class="product-recommendations" data-location="${this.options.location}">`;
        html += `<h3 class="recommendations-title">${title}</h3>`;
        html += '<div class="recommendations-grid">';

        this.recommendations.forEach(product => {
            html += this.renderProduct(product);
        });

        html += '</div>';
        html += '</div>';

        this.container.innerHTML = html;
    }

    /**
     * Get title based on recommendation type.
     */
    getTitle() {
        const titles = {
            'related': 'Related Products',
            'frequently_bought_together': 'Frequently Bought Together',
            'cross_sell': 'You May Also Like',
            'personalized': 'Recommended For You',
            'collaborative': 'Customers Also Viewed',
            'hybrid': 'Recommended Products',
        };

        return titles[this.options.algorithm] || 'Recommended Products';
    }

    /**
     * Render a single product.
     */
    renderProduct(product) {
        const productUrl = `/products/${product.slug || product.id}`;
        const imageUrl = product.thumbnail?.url || product.images?.[0]?.url || '/placeholder.jpg';
        const price = this.formatPrice(product.price_min || product.price || 0);

        return `
            <div class="recommendation-item" data-product-id="${product.id}">
                <a href="${productUrl}" 
                   class="recommendation-link" 
                   data-source-product-id="${this.options.productId}"
                   data-recommended-product-id="${product.id}"
                   data-recommendation-type="${this.options.algorithm}"
                   data-display-location="${this.options.location}">
                    <div class="recommendation-image">
                        <img src="${imageUrl}" alt="${this.escapeHtml(product.name || 'Product')}" loading="lazy">
                    </div>
                    <div class="recommendation-info">
                        <h4 class="recommendation-name">${this.escapeHtml(product.name || 'Product')}</h4>
                        <div class="recommendation-price">${price}</div>
                        ${product.average_rating ? `
                            <div class="recommendation-rating">
                                ${this.renderStars(product.average_rating)}
                                <span>(${product.total_reviews || 0})</span>
                            </div>
                        ` : ''}
                    </div>
                </a>
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
            html += '<span class="star full">★</span>';
        }
        if (hasHalfStar) {
            html += '<span class="star half">★</span>';
        }
        for (let i = 0; i < emptyStars; i++) {
            html += '<span class="star empty">☆</span>';
        }

        return html;
    }

    /**
     * Format price.
     */
    formatPrice(price) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(price / 100);
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Track clicks on recommendation links
        this.container.addEventListener('click', (e) => {
            const link = e.target.closest('.recommendation-link');
            if (link) {
                e.preventDefault();
                this.trackClick(link);
                
                // Call custom click handler if provided
                if (this.options.onProductClick) {
                    const productId = link.dataset.recommendedProductId;
                    this.options.onProductClick(productId, link.href);
                } else {
                    // Default: navigate to product
                    window.location.href = link.href;
                }
            }
        });
    }

    /**
     * Track recommendation click.
     */
    async trackClick(link) {
        try {
            await fetch('/recommendations/track-click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    source_product_id: link.dataset.sourceProductId,
                    recommended_product_id: link.dataset.recommendedProductId,
                    recommendation_type: link.dataset.recommendationType,
                    display_location: link.dataset.displayLocation,
                    recommendation_algorithm: this.options.algorithm,
                }),
            });
        } catch (error) {
            console.error('Error tracking click:', error);
        }
    }

    /**
     * Escape HTML to prevent XSS.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Update recommendations.
     */
    async updateRecommendations(algorithm, limit) {
        this.options.algorithm = algorithm;
        this.options.limit = limit;
        await this.loadRecommendations();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductRecommendations;
}


