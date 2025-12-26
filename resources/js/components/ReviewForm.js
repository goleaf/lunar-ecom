/**
 * Review Form Component
 * 
 * Handles review submission with validation.
 */

class ReviewForm {
    constructor(containerId, productId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.productId = productId;
        this.options = {
            apiUrl: options.apiUrl || `/products/${productId}/reviews`,
            onSuccess: options.onSuccess || null,
            ...options
        };

        this.init();
    }

    /**
     * Initialize the form.
     */
    init() {
        this.render();
        this.attachEventListeners();
    }

    /**
     * Render the form.
     */
    render() {
        const html = `
            <form id="review-form" class="review-form">
                <div class="form-group">
                    <label>Rating *</label>
                    <div class="rating-input" id="rating-input">
                        ${[5, 4, 3, 2, 1].map(rating => `
                            <button type="button" class="star-btn" data-rating="${rating}">☆</button>
                        `).join('')}
                    </div>
                    <input type="hidden" name="rating" id="rating-value" required>
                </div>

                <div class="form-group">
                    <label for="title">Review Title *</label>
                    <input type="text" name="title" id="title" required minlength="10" maxlength="255" 
                           placeholder="Summarize your experience">
                    <small>10-255 characters</small>
                </div>

                <div class="form-group">
                    <label for="content">Review Content *</label>
                    <textarea name="content" id="content" required minlength="10" maxlength="5000" 
                              rows="6" placeholder="Share your detailed experience..."></textarea>
                    <small>10-5000 characters</small>
                    <div class="char-count"><span id="char-count">0</span> / 5000</div>
                </div>

                <div class="form-group">
                    <label>Pros (optional)</label>
                    <div id="pros-list"></div>
                    <button type="button" class="add-pro-con-btn" data-type="pro">+ Add Pro</button>
                </div>

                <div class="form-group">
                    <label>Cons (optional)</label>
                    <div id="cons-list"></div>
                    <button type="button" class="add-pro-con-btn" data-type="con">+ Add Con</button>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="recommended" id="recommended" checked>
                        I recommend this product
                    </label>
                </div>

                <div class="form-group">
                    <label for="images">Review Images (optional, max 5)</label>
                    <input type="file" name="images[]" id="images" multiple accept="image/*" max="5">
                    <small>Maximum 5 images, 2MB each</small>
                </div>

                <button type="submit" class="submit-btn">Submit Review</button>
            </form>
        `;

        this.container.innerHTML = html;
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        const form = this.container.querySelector('#review-form');
        
        // Rating selection
        this.container.querySelectorAll('.star-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const rating = parseInt(e.target.dataset.rating);
                this.setRating(rating);
            });
        });

        // Character count
        const contentTextarea = this.container.querySelector('#content');
        contentTextarea.addEventListener('input', (e) => {
            const count = e.target.value.length;
            this.container.querySelector('#char-count').textContent = count;
        });

        // Add pro/con
        this.container.querySelectorAll('.add-pro-con-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const type = e.target.dataset.type;
                this.addProCon(type);
            });
        });

        // Form submission
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });
    }

    /**
     * Set rating.
     */
    setRating(rating) {
        this.container.querySelector('#rating-value').value = rating;
        
        this.container.querySelectorAll('.star-btn').forEach((btn, index) => {
            const btnRating = 5 - index;
            if (btnRating <= rating) {
                btn.textContent = '★';
                btn.classList.add('selected');
            } else {
                btn.textContent = '☆';
                btn.classList.remove('selected');
            }
        });
    }

    /**
     * Add pro or con field.
     */
    addProCon(type) {
        const listId = type === 'pro' ? 'pros-list' : 'cons-list';
        const list = this.container.querySelector(`#${listId}`);
        
        const item = document.createElement('div');
        item.className = 'pro-con-item';
        item.innerHTML = `
            <input type="text" name="${type}s[]" placeholder="Enter ${type}..." maxlength="255">
            <button type="button" class="remove-btn">×</button>
        `;
        
        item.querySelector('.remove-btn').addEventListener('click', () => {
            item.remove();
        });
        
        list.appendChild(item);
    }

    /**
     * Submit the form.
     */
    async submitForm() {
        const form = this.container.querySelector('#review-form');
        const formData = new FormData(form);

        // Validate
        if (!formData.get('rating')) {
            alert('Please select a rating');
            return;
        }

        try {
            const response = await fetch(this.options.apiUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                alert(data.message || 'Review submitted successfully!');
                form.reset();
                this.setRating(0);
                
                if (this.options.onSuccess) {
                    this.options.onSuccess(data);
                }
            } else {
                const errors = Object.values(data.errors || {}).flat();
                alert(errors.join('\n') || 'Failed to submit review');
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            alert('An error occurred. Please try again.');
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReviewForm;
}


