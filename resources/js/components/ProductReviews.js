/**
 * Product Reviews Component
 * 
 * Handles:
 * - Displaying reviews with filtering
 * - Review submission form
 * - Helpful voting
 * - Rating aggregation display
 */

class ProductReviews {
    constructor(containerId, productId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.productId = productId;
        this.options = {
            apiBaseUrl: options.apiBaseUrl || `/products/${productId}/reviews`,
            filter: options.filter || 'most_helpful',
            perPage: options.perPage || 10,
            onReviewSubmit: options.onReviewSubmit || null,
            ...options
        };

        this.currentFilter = this.options.filter;
        this.currentPage = 1;
        this.reviews = [];
        this.aggregateRatings = null;

        this.init();
    }

    /**
     * Initialize the component.
     */
    async init() {
        await this.loadReviews();
        this.render();
        this.attachEventListeners();
    }

    /**
     * Load reviews from API.
     */
    async loadReviews() {
        try {
            const params = new URLSearchParams({
                filter: this.currentFilter,
                page: this.currentPage,
                per_page: this.options.perPage,
            });

            const response = await fetch(`${this.options.apiBaseUrl}?${params}`);
            const data = await response.json();

            this.reviews = data.reviews;
            this.aggregateRatings = data.aggregate_ratings;
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    }

    /**
     * Render the component.
     */
    render() {
        let html = '';

        // Render aggregate ratings
        if (this.aggregateRatings) {
            html += this.renderAggregateRatings();
        }

        // Render filter buttons
        html += this.renderFilters();

        // Render reviews
        html += this.renderReviews();

        // Render pagination
        if (this.reviews && this.reviews.last_page > 1) {
            html += this.renderPagination();
        }

        this.container.innerHTML = html;
        this.attachReviewEventListeners();
    }

    /**
     * Render aggregate ratings section.
     */
    renderAggregateRatings() {
        const { average_rating, total_reviews, rating_distribution } = this.aggregateRatings;

        let html = '<div class="review-aggregate">';
        html += '<div class="review-summary">';
        html += `<div class="average-rating">${average_rating.toFixed(1)}</div>`;
        html += `<div class="rating-stars">${this.renderStars(average_rating)}</div>`;
        html += `<div class="total-reviews">Based on ${total_reviews} review${total_reviews !== 1 ? 's' : ''}</div>`;
        html += '</div>';

        // Rating distribution
        html += '<div class="rating-distribution">';
        for (let rating = 5; rating >= 1; rating--) {
            const count = this.aggregateRatings[`rating_${rating}_count`] || 0;
            const percentage = rating_distribution[rating] || 0;
            html += `
                <div class="rating-bar-item">
                    <span class="rating-label">${rating} star${rating !== 1 ? 's' : ''}</span>
                    <div class="rating-bar">
                        <div class="rating-bar-fill" style="width: ${percentage}%"></div>
                    </div>
                    <span class="rating-count">${count}</span>
                </div>
            `;
        }
        html += '</div>';
        html += '</div>';

        return html;
    }

    /**
     * Render filter buttons.
     */
    renderFilters() {
        const filters = [
            { value: 'most_helpful', label: 'Most Helpful' },
            { value: 'most_recent', label: 'Most Recent' },
            { value: 'highest_rating', label: 'Highest Rating' },
            { value: 'lowest_rating', label: 'Lowest Rating' },
            { value: 'verified_only', label: 'Verified Purchases' },
        ];

        let html = '<div class="review-filters">';
        filters.forEach(filter => {
            html += `
                <button class="filter-btn ${this.currentFilter === filter.value ? 'active' : ''}" 
                        data-filter="${filter.value}">
                    ${filter.label}
                </button>
            `;
        });
        html += '</div>';

        return html;
    }

    /**
     * Render reviews list.
     */
    renderReviews() {
        if (!this.reviews || this.reviews.data.length === 0) {
            return '<div class="no-reviews">No reviews yet. Be the first to review this product!</div>';
        }

        let html = '<div class="reviews-list">';
        this.reviews.data.forEach(review => {
            html += this.renderReview(review);
        });
        html += '</div>';

        return html;
    }

    /**
     * Render a single review.
     */
    renderReview(review) {
        const verifiedBadge = review.is_verified_purchase 
            ? '<span class="verified-badge">✓ Verified Purchase</span>' 
            : '';
        
        const recommendedBadge = review.recommended 
            ? '<span class="recommended-badge">✓ Recommended</span>' 
            : '';

        let html = `<div class="review-item" data-review-id="${review.id}">`;
        html += '<div class="review-header">';
        html += `<div class="review-rating">${this.renderStars(review.rating)}</div>`;
        html += `<div class="review-title">${this.escapeHtml(review.title)}</div>`;
        html += `${verifiedBadge} ${recommendedBadge}`;
        html += '</div>';

        html += '<div class="review-meta">';
        html += `<span class="review-author">${review.customer?.name || 'Anonymous'}</span>`;
        html += `<span class="review-date">${this.formatDate(review.created_at)}</span>`;
        html += '</div>';

        html += `<div class="review-content">${this.escapeHtml(review.content)}</div>`;

        // Pros and Cons
        if (review.pros && review.pros.length > 0) {
            html += '<div class="review-pros">';
            html += '<strong>Pros:</strong><ul>';
            review.pros.forEach(pro => {
                html += `<li>${this.escapeHtml(pro)}</li>`;
            });
            html += '</ul></div>';
        }

        if (review.cons && review.cons.length > 0) {
            html += '<div class="review-cons">';
            html += '<strong>Cons:</strong><ul>';
            review.cons.forEach(con => {
                html += `<li>${this.escapeHtml(con)}</li>`;
            });
            html += '</ul></div>';
        }

        // Review images
        if (review.media && review.media.length > 0) {
            html += '<div class="review-images">';
            review.media.forEach(media => {
                html += `<img src="${media.url}" alt="Review image" class="review-image">`;
            });
            html += '</div>';
        }

        // Admin response
        if (review.admin_response) {
            html += '<div class="admin-response">';
            html += '<strong>Admin Response:</strong>';
            html += `<p>${this.escapeHtml(review.admin_response)}</p>`;
            html += '</div>';
        }

        // Helpful votes
        html += '<div class="review-actions">';
        html += `<button class="helpful-btn" data-review-id="${review.id}" data-helpful="true">
            Helpful (${review.helpful_count})
        </button>`;
        html += `<button class="not-helpful-btn" data-review-id="${review.id}" data-helpful="false">
            Not Helpful (${review.not_helpful_count})
        </button>`;
        html += `<button class="report-btn" data-review-id="${review.id}">Report</button>`;
        html += '</div>';

        html += '</div>';

        return html;
    }

    /**
     * Render pagination.
     */
    renderPagination() {
        const { current_page, last_page } = this.reviews;

        let html = '<div class="reviews-pagination">';
        
        if (current_page > 1) {
            html += `<button class="page-btn" data-page="${current_page - 1}">Previous</button>`;
        }

        for (let i = 1; i <= last_page; i++) {
            if (i === 1 || i === last_page || (i >= current_page - 2 && i <= current_page + 2)) {
                html += `<button class="page-btn ${i === current_page ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (i === current_page - 3 || i === current_page + 3) {
                html += '<span class="page-ellipsis">...</span>';
            }
        }

        if (current_page < last_page) {
            html += `<button class="page-btn" data-page="${current_page + 1}">Next</button>`;
        }

        html += '</div>';

        return html;
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
            html += '<span class="star empty">★</span>';
        }

        return html;
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        // Filter buttons
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                this.currentFilter = e.target.dataset.filter;
                this.currentPage = 1;
                this.loadReviews().then(() => this.render());
            }

            // Pagination
            if (e.target.classList.contains('page-btn')) {
                this.currentPage = parseInt(e.target.dataset.page);
                this.loadReviews().then(() => this.render());
            }
        });
    }

    /**
     * Attach review-specific event listeners.
     */
    attachReviewEventListeners() {
        // Helpful/Not Helpful buttons
        this.container.addEventListener('click', async (e) => {
            if (e.target.classList.contains('helpful-btn') || e.target.classList.contains('not-helpful-btn')) {
                const reviewId = e.target.dataset.reviewId;
                const isHelpful = e.target.dataset.helpful === 'true';

                await this.markHelpful(reviewId, isHelpful);
            }

            // Report button
            if (e.target.classList.contains('report-btn')) {
                const reviewId = e.target.dataset.reviewId;
                await this.reportReview(reviewId);
            }
        });
    }

    /**
     * Mark review as helpful.
     */
    async markHelpful(reviewId, isHelpful) {
        try {
            const response = await fetch(`/reviews/${reviewId}/helpful`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ is_helpful: isHelpful }),
            });

            const data = await response.json();

            if (response.ok) {
                // Reload reviews to update counts
                await this.loadReviews();
                this.render();
            } else {
                alert(data.message || 'Failed to record vote');
            }
        } catch (error) {
            console.error('Error marking helpful:', error);
            alert('An error occurred. Please try again.');
        }
    }

    /**
     * Report a review.
     */
    async reportReview(reviewId) {
        if (!confirm('Are you sure you want to report this review?')) {
            return;
        }

        try {
            const response = await fetch(`/reviews/${reviewId}/report`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();

            if (response.ok) {
                alert(data.message || 'Review reported successfully');
            } else {
                alert(data.message || 'Failed to report review');
            }
        } catch (error) {
            console.error('Error reporting review:', error);
            alert('An error occurred. Please try again.');
        }
    }

    /**
     * Format date.
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    /**
     * Escape HTML to prevent XSS.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductReviews;
}


