/**
 * Stock Notification Subscription Form
 * 
 * Form for subscribing to back-in-stock notifications.
 */

class StockNotificationForm {
    constructor(containerId, variantId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.variantId = variantId;
        this.options = {
            apiUrl: options.apiUrl || `/stock-notifications/variants/${variantId}/subscribe`,
            checkUrl: options.checkUrl || `/stock-notifications/variants/${variantId}/check`,
            onSubscribe: options.onSubscribe || null,
            ...options
        };

        this.isSubscribed = false;
        this.init();
    }

    /**
     * Initialize the component.
     */
    async init() {
        await this.checkSubscription();
        this.render();
        this.attachEventListeners();
    }

    /**
     * Check if email is already subscribed.
     */
    async checkSubscription() {
        const email = this.getStoredEmail();
        if (!email) {
            return;
        }

        try {
            const response = await fetch(`${this.options.checkUrl}?email=${encodeURIComponent(email)}`);
            const data = await response.json();
            this.isSubscribed = data.subscribed || false;
        } catch (error) {
            console.error('Error checking subscription:', error);
        }
    }

    /**
     * Get stored email (from user account or form).
     */
    getStoredEmail() {
        // Try to get from user data if available
        if (window.userData && window.userData.email) {
            return window.userData.email;
        }
        return null;
    }

    /**
     * Render the form.
     */
    render() {
        const email = this.getStoredEmail();
        
        let html = '<div class="stock-notification-form">';
        
        if (this.isSubscribed) {
            html += '<div class="notification-subscribed">';
            html += '<p class="success-message">✓ You are subscribed to back-in-stock notifications for this product.</p>';
            html += '</div>';
        } else {
            html += '<div class="notification-form">';
            html += '<h4>Notify Me When Back in Stock</h4>';
            html += '<p class="form-description">Get an email when this product is back in stock.</p>';
            html += '<form id="stock-notification-form">';
            html += '<div class="form-group">';
            html += '<label for="notification-email">Email Address</label>';
            html += `<input type="email" id="notification-email" class="form-control" value="${email || ''}" placeholder="your@email.com" required>`;
            html += '</div>';
            html += '<button type="submit" class="btn-subscribe" id="subscribe-btn">Notify Me</button>';
            html += '</form>';
            html += '</div>';
        }

        html += '</div>';

        this.container.innerHTML = html;
    }

    /**
     * Attach event listeners.
     */
    attachEventListeners() {
        const form = this.container.querySelector('#stock-notification-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.subscribe();
            });
        }
    }

    /**
     * Subscribe to notifications.
     */
    async subscribe() {
        const emailInput = this.container.querySelector('#notification-email');
        const email = emailInput?.value.trim();

        if (!email) {
            this.showError('Please enter your email address');
            return;
        }

        if (!this.isValidEmail(email)) {
            this.showError('Please enter a valid email address');
            return;
        }

        const submitBtn = this.container.querySelector('#subscribe-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Subscribing...';
        }

        try {
            const response = await fetch(this.options.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ email }),
            });

            const data = await response.json();

            if (data.success) {
                this.isSubscribed = true;
                this.render();
                this.showSuccess(data.message || 'You will be notified when this product is back in stock');
                
                if (this.options.onSubscribe) {
                    this.options.onSubscribe(data);
                }
            } else {
                this.showError(data.message || 'Failed to subscribe. Please try again.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Notify Me';
                }
            }
        } catch (error) {
            console.error('Error subscribing:', error);
            this.showError('An error occurred. Please try again.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Notify Me';
            }
        }
    }

    /**
     * Validate email.
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Show success message.
     */
    showSuccess(message) {
        // Simple notification - can be enhanced
        const notification = document.createElement('div');
        notification.className = 'notification-success';
        notification.textContent = message;
        this.container.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Show error message.
     */
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'notification-error';
        errorDiv.textContent = message;
        
        const form = this.container.querySelector('.notification-form');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StockNotificationForm;
}

