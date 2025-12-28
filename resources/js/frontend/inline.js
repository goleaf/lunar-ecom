// Frontend: migrated inline Blade scripts live here (no <script> blocks in Blade).
// We intentionally expose some functions on window because existing Blade markup
// still calls them via onclick / x-data.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function postJson(url, payload = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    let data = null;
    try {
        data = await response.json();
    } catch (_) {
        // ignore
    }

    return { response, data };
}

function isFrontend() {
    return document.body?.dataset?.app === 'frontend';
}

function initCartWidget() {
    const widget = document.getElementById('cart-widget');
    const url = widget?.dataset?.summaryUrl;
    if (!widget || !url) return;

    async function updateCartCount() {
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();
            const countElement = document.getElementById('cart-count');
            const itemCount = data?.cart?.item_count || 0;

            if (itemCount > 0) {
                if (!countElement) {
                    const badge = document.createElement('span');
                    badge.id = 'cart-count';
                    badge.className =
                        'absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center';
                    widget.appendChild(badge);
                }
                const badge = document.getElementById('cart-count');
                badge.textContent = itemCount > 99 ? '99+' : String(itemCount);
                badge.style.display = 'flex';
            } else if (countElement) {
                countElement.style.display = 'none';
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    }

    window.updateCartCount = updateCartCount;
    document.addEventListener('DOMContentLoaded', updateCartCount);
    document.addEventListener('cartUpdated', updateCartCount);
}

function initQaForms() {
    const questionForm = document.getElementById('question-form');
    const questionMessage = document.getElementById('question-message');

    if (questionForm?.dataset?.url && questionMessage) {
        questionForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            questionMessage.textContent = 'Submitting...';

            const formData = new FormData(questionForm);
            const payload = Object.fromEntries(formData.entries());

            try {
                const { response, data } = await postJson(questionForm.dataset.url, payload);
                if (response.ok) {
                    questionMessage.textContent = data?.message || 'Question submitted.';
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    questionMessage.textContent = data?.message || 'Unable to submit question.';
                }
            } catch (_) {
                questionMessage.textContent = 'Unable to submit question.';
            }
        });
    }

    document.querySelectorAll('.answer-form[data-url]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            try {
                const { response } = await postJson(form.dataset.url, payload);
                if (response.ok) {
                    window.location.reload();
                }
            } catch (_) {
                alert('Failed to submit answer.');
            }
        });
    });

    // Helpful votes on questions/answers (Q&A page).
    document.querySelectorAll('.mark-helpful[data-url]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (response.ok) {
                    window.location.reload();
                }
            } catch (_) {
                alert('Unable to mark helpful.');
            }
        });
    });
}

function initComingSoonForm() {
    const form = document.getElementById('comingSoonForm');
    const messageDiv = document.getElementById('comingSoonMessage');
    const url = form?.dataset?.url;
    if (!form || !messageDiv || !url) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
                body: formData,
            });

            const data = await response.json();
            if (data?.success) {
                messageDiv.innerHTML = '<span class="text-green-600">✓ ' + data.message + '</span>';
                form.reset();
            } else {
                messageDiv.innerHTML =
                    '<span class="text-red-600">✗ ' + (data?.message || 'Something went wrong') + '</span>';
            }
        } catch (_) {
            messageDiv.innerHTML = '<span class="text-red-600">✗ Failed to subscribe. Please try again.</span>';
        }
    });
}

function initRecommendationsTracking() {
    const trackUrl =
        document.querySelector('[data-recommendations-track-click-url]')?.getAttribute('data-recommendations-track-click-url') ||
        '/recommendations/track-click';

    document.addEventListener('click', (e) => {
        const container =
            e.target.closest('.product-recommendations, .frequently-bought-together, .customers-also-viewed');
        if (!container) return;

        const productCard = e.target.closest('.recommended-product');
        if (!productCard) return;

        const sourceProductId = container.dataset.sourceProductId;
        const recommendationType = productCard.dataset.recommendationType || container.dataset.type || 'hybrid';
        const location = container.dataset.location || 'product_page';
        const recommendedProductId = productCard.dataset.productId;

        if (!sourceProductId || !recommendedProductId) return;

        // Fire and forget.
        postJson(trackUrl, {
            source_product_id: sourceProductId,
            recommended_product_id: recommendedProductId,
            recommendation_type: recommendationType,
            display_location: location,
            recommendation_algorithm: container.dataset.algorithm || 'hybrid',
        }).catch((err) => console.error('Failed to track recommendation click:', err));
    });
}

function initReviewForm() {
    const form = document.getElementById('review-form');
    if (!form) return;

    let selectedRating = 0;

    window.setRating = function setRating(rating) {
        selectedRating = rating;
        document.getElementById('rating-input').value = rating;

        const stars = document.querySelectorAll('.rating-star');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    };

    window.addProField = function addProField() {
        const container = document.getElementById('pros-container');
        const div = document.createElement('div');
        div.className = 'flex gap-2';
        div.innerHTML = `
            <input type="text" name="pros[]" class="flex-1 border rounded px-3 py-2" placeholder="Enter a pro">
            <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-red-200 rounded hover:bg-red-300">-</button>
        `;
        container.appendChild(div);
    };

    window.addConField = function addConField() {
        const container = document.getElementById('cons-container');
        const div = document.createElement('div');
        div.className = 'flex gap-2';
        div.innerHTML = `
            <input type="text" name="cons[]" class="flex-1 border rounded px-3 py-2" placeholder="Enter a con">
            <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-red-200 rounded hover:bg-red-300">-</button>
        `;
        container.appendChild(div);
    };

    form.addEventListener('submit', (e) => {
        if (!selectedRating) {
            e.preventDefault();
            alert('Please select a rating');
        }
    });
}

function initReviewHelpful() {
    window.markHelpful = function markHelpful(reviewId, isHelpful) {
        postJson(`/reviews/${reviewId}/helpful`, { is_helpful: isHelpful })
            .then(({ data }) => {
                if (data?.message) {
                    location.reload();
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('Unable to record your vote. Please try again.');
            });
    };
}

function initDownloads() {
    window.resendEmail = function resendEmail(downloadId) {
        postJson(`/downloads/${downloadId}/resend-email`, {})
            .then(({ data }) => {
                if (data?.success) {
                    alert('Download instructions email has been sent.');
                } else {
                    alert('Failed to send email. Please try again.');
                }
            })
            .catch(() => alert('Failed to send email. Please try again.'));
    };

    window.showLicenseKey = function showLicenseKey(licenseKey) {
        document.getElementById('licenseKeyText').textContent = licenseKey;
        document.getElementById('licenseModal').classList.remove('hidden');
    };

    window.closeLicenseModal = function closeLicenseModal() {
        document.getElementById('licenseModal').classList.add('hidden');
    };

    window.copyLicenseKey = function copyLicenseKey() {
        const licenseKey = document.getElementById('licenseKeyText').textContent;
        navigator.clipboard.writeText(licenseKey).then(() => {
            alert('License key copied to clipboard!');
        });
    };
}

function initCartPage() {
    // Cart page: handle updates/removals/discounts via AJAX and reload for updated totals.
    document.querySelectorAll('.cart-update-form, .cart-remove-form, .discount-form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data?.success) {
                        window.location.reload();
                    } else {
                        alert(data?.message || 'An error occurred. Please try again.');
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });
    });

    // Keep cart widget in sync.
    document.dispatchEvent(new Event('cartUpdated'));
}

function initBundleShow() {
    const form = document.getElementById('addToCartForm');
    const cartUrl = form?.dataset?.cartUrl;
    if (!form || !cartUrl) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            window.location.href = cartUrl;
            return;
        }

        alert(data?.message || 'Failed to add bundle to cart');
        if (data?.errors) {
            console.error(data.errors);
        }
    });
}

// Alpine helpers that were previously defined inline in Blade.
window.searchAutocomplete = function searchAutocomplete() {
    return {
        query: '',
        suggestions: [],
        history: [],
        popularSearches: [],
        showDropdown: false,
        selectedIndex: -1,
        loading: false,

        init() {
            this.autocompleteUrl = this.$el?.dataset?.autocompleteUrl;
            this.popularUrl = this.$el?.dataset?.popularUrl;
            this.searchUrl = this.$el?.dataset?.searchUrl;

            this.loadHistory();
            this.loadPopularSearches();
        },

        async search() {
            if (this.query.length < 2 || !this.autocompleteUrl) {
                this.suggestions = [];
                return;
            }

            this.loading = true;
            this.showDropdown = true;

            try {
                const response = await fetch(`${this.autocompleteUrl}?q=${encodeURIComponent(this.query)}&limit=10`);
                const data = await response.json();
                this.suggestions = data.data || [];
            } catch (error) {
                console.error('Search error:', error);
                this.suggestions = [];
            } finally {
                this.loading = false;
            }
        },

        async loadHistory() {
            if (!this.autocompleteUrl) return;
            try {
                const response = await fetch(`${this.autocompleteUrl}?q=&limit=5`);
                const data = await response.json();
                this.history = data.history || [];
            } catch (error) {
                console.error('History load error:', error);
            }
        },

        async loadPopularSearches() {
            if (!this.popularUrl) return;
            try {
                const response = await fetch(`${this.popularUrl}?limit=5`);
                const data = await response.json();
                this.popularSearches = data.data || [];
            } catch (error) {
                console.error('Popular searches load error:', error);
            }
        },

        navigateDown() {
            if (this.selectedIndex < this.suggestions.length - 1) {
                this.selectedIndex++;
            }
        },

        navigateUp() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
        },

        selectSuggestion() {
            if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
                window.location.href = this.suggestions[this.selectedIndex].url;
            } else if (this.query.length > 0) {
                this.handleSubmit();
            }
        },

        handleSubmit() {
            if (this.query.length > 0 && this.searchUrl) {
                window.location.href = `${this.searchUrl}?q=${encodeURIComponent(this.query)}`;
            }
        },
    };
};

window.imageUploader = function imageUploader(modelId, modelType, collectionName) {
    return {
        modelId,
        modelType,
        collectionName,
        isDragging: false,
        uploading: false,
        uploadedImages: [],
        error: null,
        success: null,
        fileInput: null,

        init() {
            this.loadExistingImages();
        },

        async loadExistingImages() {
            // This would load existing images for the model
            // Implementation depends on your API structure
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.uploadFiles(files);
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = Array.from(event.dataTransfer.files).filter((file) => file.type.startsWith('image/'));
            if (files.length > 0) {
                this.uploadFiles(files);
            }
        },

        async uploadFiles(files) {
            if (files.length === 0) return;

            this.uploading = true;
            this.error = null;
            this.success = null;

            const formData = new FormData();
            files.forEach((file, index) => {
                formData.append(`images[${index}]`, file);
            });

            try {
                let url;
                if (this.modelType === 'product') {
                    url = `/media/product/${this.modelId}/upload`;
                } else if (this.modelType === 'collection') {
                    url = `/media/collection/${this.modelId}/upload`;
                } else if (this.modelType === 'brand') {
                    url = `/media/brand/${this.modelId}/logo`;
                    formData.delete('images[0]');
                    formData.append('logo', files[0]);
                } else {
                    throw new Error('Invalid model type');
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    if (Array.isArray(data.media)) {
                        this.uploadedImages.push(...data.media);
                    } else {
                        this.uploadedImages.push(data.media);
                    }
                    this.success = `Successfully uploaded ${files.length} image(s)`;
                    this.$dispatch('images-uploaded', data.media);
                } else {
                    this.error = data.error || 'Upload failed';
                }
            } catch (error) {
                this.error = 'Failed to upload images: ' + error.message;
            } finally {
                this.uploading = false;
            }
        },

        async deleteImage(mediaId, index) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }

            try {
                const response = await fetch(`/media/${this.modelType}/${this.modelId}/${mediaId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.uploadedImages.splice(index, 1);
                    this.success = 'Image deleted successfully';
                    this.$dispatch('image-deleted', mediaId);
                } else {
                    this.error = data.error || 'Failed to delete image';
                }
            } catch (error) {
                this.error = 'Failed to delete image: ' + error.message;
            }
        },
    };
};

function initProductFilters() {
    const container = document.getElementById('product-filters-container');
    if (!container || !window.ProductAttributeFilter) return;

    if (container.dataset.initialized === '1') return;
    container.dataset.initialized = '1';

    const categoryId = container.dataset.categoryId ? Number(container.dataset.categoryId) : null;
    const productTypeId = container.dataset.productTypeId ? Number(container.dataset.productTypeId) : null;
    const apiBaseUrl = container.dataset.apiBaseUrl || '/api/filters';

    // eslint-disable-next-line no-new
    new window.ProductAttributeFilter('product-filters-container', {
        categoryId,
        productTypeId,
        apiBaseUrl,
        productsContainerId: 'products-container',
        filterLogic: 'and',
        onFilterChange: function (data) {
            const productsContainer = document.getElementById('products-container');
            if (!productsContainer) return;

            let html = '<div class="products-grid">';
            (data?.data || []).forEach((product) => {
                html += `
                    <div class="product-card">
                        <h3>${product?.name || 'Product'}</h3>
                    </div>
                `;
            });
            html += '</div>';

            if (data?.meta?.last_page > 1) {
                html += '<div class="pagination">';
                for (let i = 1; i <= data.meta.last_page; i++) {
                    html += `<a href="?page=${i}" class="page-link ${i === data.meta.current_page ? 'active' : ''}">${i}</a>`;
                }
                html += '</div>';
            }

            productsContainer.innerHTML = html;
        },
    });
}

export function initFrontendInlineMigrations() {
    if (!isFrontend()) return;

    initCartWidget();
    initQaForms();
    initComingSoonForm();
    initRecommendationsTracking();
    initReviewForm();
    initReviewHelpful();
    initDownloads();
    initCartPage();
    initBundleShow();
    initProductFilters();
}

initFrontendInlineMigrations();


