/**
 * Flash Sale Countdown Timer Component
 */
class FlashSaleCountdown {
    constructor(element, endTime) {
        this.element = element;
        this.endTime = new Date(endTime);
        this.interval = null;
        this.init();
    }

    init() {
        this.update();
        this.interval = setInterval(() => this.update(), 1000);
    }

    update() {
        const now = new Date();
        const distance = this.endTime - now;

        if (distance < 0) {
            this.element.innerHTML = '<span class="text-red-600 font-bold">Sale Ended</span>';
            clearInterval(this.interval);
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        this.element.innerHTML = `
            <div class="flex gap-2 text-center">
                ${days > 0 ? `<div class="bg-red-600 text-white px-3 py-2 rounded"><div class="text-2xl font-bold">${days}</div><div class="text-xs">Days</div></div>` : ''}
                <div class="bg-red-600 text-white px-3 py-2 rounded">
                    <div class="text-2xl font-bold">${String(hours).padStart(2, '0')}</div>
                    <div class="text-xs">Hours</div>
                </div>
                <div class="bg-red-600 text-white px-3 py-2 rounded">
                    <div class="text-2xl font-bold">${String(minutes).padStart(2, '0')}</div>
                    <div class="text-xs">Minutes</div>
                </div>
                <div class="bg-red-600 text-white px-3 py-2 rounded">
                    <div class="text-2xl font-bold">${String(seconds).padStart(2, '0')}</div>
                    <div class="text-xs">Seconds</div>
                </div>
            </div>
        `;
    }

    destroy() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    }
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-flash-sale-end]').forEach(element => {
        const endTime = element.getAttribute('data-flash-sale-end');
        new FlashSaleCountdown(element, endTime);
    });
});

export default FlashSaleCountdown;

